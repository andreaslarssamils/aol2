<?php

namespace App\Actions\Revisions;

use App\Casts\PlainText;
use App\Casts\SanitizedHtml;
use App\Enums\RevisionStatus;
use App\Models\BusinessLocation;
use App\Models\Revision;
use App\Models\User;
use App\Support\HtmlSanitizer;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Lägger en föreslagen ändring i granskningskön. Payloaden är hela den föreslagna
 * uppsättningen fält (inte en diff) och valideras mot modellens form request, så att
 * ogiltig data aldrig hamnar i kön. Har verksamheten auto_approve godkänns den direkt.
 *
 * Ägarlänkar skyddas: på en befintlig rad strippas de ur payloaden oavsett vem som skickar in,
 * och nytt innehåll får bara skapas under en verksamhet inskickaren är kopplad till.
 * Admin och redaktör är undantagna från båda reglerna.
 *
 * Innehållet saneras före valideringen (App\Support\HtmlSanitizer): rich text med
 * editorial-profilen för redaktionen och den snävare owner-profilen för alla andra; rena
 * textfält (ingress, lunchrätter) strippas från all markup oavsett roll.
 *
 * En rad har högst en väntande revision: ett nytt inskick ersätter den gamla, som sätts till
 * rejected med kommentaren SUPERSEDED_COMMENT. Väntande media rörs inte – de hör till det nya
 * inskicket lika mycket som till det gamla.
 */
class SubmitRevision
{
    /** Fält som avgör vem innehållet tillhör – ändras aldrig via en revision på en befintlig rad. */
    public const array OWNERSHIP_FIELDS = ['business_id', 'business_location_id', 'author_id', 'created_by', 'parent_event_id'];

    public const string SUPERSEDED_COMMENT = 'Ersatt av nyare inskick';

    public function __construct(private ApproveRevision $approveRevision, private HtmlSanitizer $sanitizer) {}

    /**
     * @param  Model  $model  Innehåll som använder HasRevisions. Finns raden inte skapas den som utkast.
     * @param  array<string, mixed>  $payload
     *
     * @throws ValidationException
     * @throws AuthorizationException
     */
    public function handle(Model $model, array $payload, User $user): Revision
    {
        if (! $user->isEditorial() && $model->exists) {
            $payload = Arr::except($payload, self::OWNERSHIP_FIELDS);
        }

        $payload = $this->sanitize($model, $payload, $user);
        $payload = $this->validate($model, $payload);

        if (! $user->isEditorial() && ! $model->exists) {
            $this->assertBelongsToSubmitter($payload, $user);
        }

        $revision = DB::transaction(function () use ($model, $payload, $user): Revision {
            if ($model->exists) {
                $this->supersedePending($model, $user);
            } else {
                $this->createDraft($model, $payload);
            }

            return $model->revisions()->create([
                'submitted_by' => $user->id,
                'payload' => $payload,
            ]);
        });

        if ($model->revisionBusiness()?->auto_approve) {
            return $this->approveRevision->handle($revision, null, 'Godkänd automatiskt');
        }

        return $revision;
    }

    /**
     * Innehållet saneras innan valideringen, så att det som valideras är det som sparas och kön
     * aldrig innehåller osanerat innehåll: rich text-kolumnerna (casten SanitizedHtml) med
     * rollens profil, rena textkolumner (casten PlainText) strippas från all markup.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    protected function sanitize(Model $model, array $payload, User $user): array
    {
        $profiles = [
            ...array_fill_keys(SanitizedHtml::columns($model), $user->isEditorial() ? HtmlSanitizer::EDITORIAL : HtmlSanitizer::OWNER),
            ...array_fill_keys(PlainText::columns($model), HtmlSanitizer::TEXT),
        ];

        foreach ($profiles as $column => $profile) {
            if (array_key_exists($column, $payload)) {
                $payload[$column] = $this->sanitizer->sanitizeValue($payload[$column], $profile);
            }
        }

        return $payload;
    }

    /**
     * Bara validerade fält sparas; nycklar utanför form requesten (t.ex. status eller
     * auto_approve) faller bort. Slugs normaliseras så att payloaden visar exakt det som sätts.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    protected function validate(Model $model, array $payload): array
    {
        $request = $model::revisionRequest();

        $validated = Validator::make($payload, $request->rules(), $request->messages(), $request->attributes())->validate();

        if (isset($validated['slugs'])) {
            $validated['slugs'] = collect($validated['slugs'])
                ->map(fn (?string $slug): string => Str::slug((string) $slug))
                ->filter()
                ->all();
        }

        return $validated;
    }

    /**
     * Nytt innehåll måste höra till en verksamhet inskickaren har en business_user-rad på:
     * via business_id, eller via business_location_id (lunchmenyer, evenemang).
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws AuthorizationException
     */
    protected function assertBelongsToSubmitter(array $payload, User $user): void
    {
        $businessId = $payload['business_id']
            ?? BusinessLocation::query()->whereKey($payload['business_location_id'] ?? null)->value('business_id');

        if ($businessId === null || ! $user->businesses()->whereKey($businessId)->exists()) {
            throw new AuthorizationException('Nytt innehåll måste höra till en verksamhet du är kopplad till.');
        }
    }

    /** Tidigare väntande revisioner för raden avvisas – det nya inskicket är hela den föreslagna uppsättningen fält. */
    protected function supersedePending(Model $model, User $user): void
    {
        $model->revisions()->pending()->update([
            'status' => RevisionStatus::Rejected,
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
            'review_comment' => self::SUPERSEDED_COMMENT,
        ]);
    }

    /**
     * Nytt innehåll skapas som utkast – inte publikt – med de föreslagna fälten.
     * Revisionen är inkorgsposten; godkännandet publicerar raden och sätter slugs och kategorier.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function createDraft(Model $model, array $payload): void
    {
        $model->fill(Arr::except($payload, ['slugs', 'categories']))->save();
    }
}
