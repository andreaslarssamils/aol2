<?php

namespace App\Models;

use App\Enums\RevisionStatus;
use BackedEnum;
use Database\Factories\RevisionFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Inkorg, inte tillstånd. Raden i innehållstabellen rörs först vid godkännande, då payload
 * skrivs dit, status sätts och applied_at stämplas. Avslag rör aldrig raden.
 * Godkända rader raderas aldrig – de är versionshistorik. Flödet bor i App\Actions\Revisions.
 */
#[Fillable([
    'revisionable_type', 'revisionable_id', 'submitted_by', 'payload',
    'status', 'reviewed_by', 'reviewed_at', 'review_comment', 'applied_at',
])]
class Revision extends Model
{
    /** @use HasFactory<RevisionFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => RevisionStatus::Pending->value,
    ];

    protected static function booted(): void
    {
        static::deleting(function (Revision $revision): void {
            if ($revision->status === RevisionStatus::Approved) {
                throw new LogicException("Revision {$revision->id} är godkänd och därmed versionshistorik – den raderas aldrig.");
            }
        });
    }

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => RevisionStatus::class,
            'reviewed_at' => 'datetime',
            'applied_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', RevisionStatus::Pending);
    }

    #[Scope]
    protected function approved(Builder $query): void
    {
        $query->where('status', RevisionStatus::Approved);
    }

    public function revisionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Nuvarande rad mot payload, fält för fält. Översättningsbara fält plattas ut per språk
     * ("titel.sv"), slugs läses från slugs-tabellen och kategorier visas med namn. Föreslagna
     * värden körs genom modellens casts så att t.ex. datum och booleans jämförs lika.
     *
     * @return list<array{field: string, current: ?string, proposed: ?string, changed: bool}>
     */
    public function diff(): array
    {
        $model = $this->revisionable;
        $probe = $model?->newInstance() ?? new (Relation::getMorphedModel($this->revisionable_type) ?? $this->revisionable_type);
        $rows = [];

        foreach ($this->orderedPayload($probe) as $field => $proposed) {
            if ($field === 'slugs') {
                foreach ((array) $proposed as $locale => $slug) {
                    $rows[] = $this->diffRow("slug.{$locale}", $model?->slugFor($locale), $slug);
                }

                continue;
            }

            if ($field === 'categories') {
                $currentIds = $model?->categories->pluck('id')->all() ?? [];
                $rows[] = $this->diffRow('categories', $this->categoryNames($currentIds), $this->categoryNames((array) $proposed));

                continue;
            }

            $probe->setAttribute($field, $proposed);
            $current = $model?->getAttribute($field);
            $proposed = $probe->getAttribute($field);

            if ((is_array($proposed) && ! array_is_list($proposed)) || (is_array($current) && ! array_is_list($current))) {
                foreach ($this->diffKeys((array) $current, (array) $proposed) as $key) {
                    $rows[] = $this->diffRow("{$field}.{$key}", $current[$key] ?? null, $proposed[$key] ?? null);
                }

                continue;
            }

            $rows[] = $this->diffRow($field, $current, $proposed);
        }

        return $rows;
    }

    /**
     * Payloaden i form requestens fältordning – jsonb sorterar annars om nycklarna efter längd.
     *
     * @return array<string, mixed>
     */
    protected function orderedPayload(Model $probe): array
    {
        $rank = array_flip(array_unique(array_map(
            fn (string $rule): string => explode('.', $rule, 2)[0],
            array_keys($probe::revisionRequest()->rules()),
        )));

        return collect($this->payload ?? [])
            ->sortBy(fn (mixed $value, string $field): int => $rank[$field] ?? PHP_INT_MAX)
            ->all();
    }

    /**
     * Nycklar i ett översättningsbart fält: konfigurerade språk i sin ordning först, sedan övriga.
     *
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $proposed
     * @return list<string>
     */
    protected function diffKeys(array $current, array $proposed): array
    {
        $rank = array_flip(array_keys(config('app.locales')));
        $keys = array_values(array_unique([...array_keys($current), ...array_keys($proposed)]));

        usort($keys, fn (string $a, string $b): int => (($rank[$a] ?? PHP_INT_MAX) <=> ($rank[$b] ?? PHP_INT_MAX)) ?: strcmp($a, $b));

        return $keys;
    }

    /** @return array{field: string, current: ?string, proposed: ?string, changed: bool} */
    protected function diffRow(string $field, mixed $current, mixed $proposed): array
    {
        $current = $this->diffValue($current);
        $proposed = $this->diffValue($proposed);

        return ['field' => $field, 'current' => $current, 'proposed' => $proposed, 'changed' => $current !== $proposed];
    }

    protected function diffValue(mixed $value): ?string
    {
        return match (true) {
            $value === null, $value === '' => null,
            $value instanceof DateTimeInterface => Carbon::instance($value)->format('Y-m-d H:i'),
            $value instanceof BackedEnum => (string) $value->value,
            is_bool($value) => $value ? 'Ja' : 'Nej',
            is_array($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            default => (string) $value,
        };
    }

    /** @param  array<int, int|string>  $ids */
    protected function categoryNames(array $ids): ?string
    {
        if ($ids === []) {
            return null;
        }

        $locale = array_key_first(config('app.locales'));

        return Category::query()
            ->findMany($ids)
            ->map(fn (Category $category): string => $category->namn[$locale] ?? $category->slug)
            ->sort()
            ->join(', ');
    }
}
