<?php

namespace App\Actions\Revisions;

use App\Models\Revision;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Lägger en föreslagen ändring i granskningskön. Payloaden är hela den föreslagna
 * uppsättningen fält (inte en diff) och valideras mot modellens form request, så att
 * ogiltig data aldrig hamnar i kön. Har verksamheten auto_approve godkänns den direkt.
 */
class SubmitRevision
{
    public function __construct(private ApproveRevision $approveRevision) {}

    /**
     * @param  Model  $model  Innehåll som använder HasRevisions
     * @param  array<string, mixed>  $payload
     *
     * @throws ValidationException
     */
    public function handle(Model $model, array $payload, User $user): Revision
    {
        $revision = $model->revisions()->create([
            'submitted_by' => $user->id,
            'payload' => $this->validate($model, $payload),
        ]);

        if ($model->revisionBusiness()?->auto_approve) {
            return $this->approveRevision->handle($revision, null, 'Godkänd automatiskt');
        }

        return $revision;
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
}
