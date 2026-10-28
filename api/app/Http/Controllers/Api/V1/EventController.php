<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CategoryType;
use App\Http\Resources\Api\V1\EventResource;
use App\Models\Event;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

/**
 * Kalendern: publicerade evenemang i sajtens kommuner. Förekomster av återkommande event
 * (parent_event_id satt) ingår, mallposterna med recurrence_rule gör det inte.
 */
class EventController extends ApiController
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            ...$this->paginationRules(),
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
            'category' => ['sometimes', 'string', 'max:255'],
        ]);

        $from = $this->parseDate($validated['from'] ?? null) ?? now();
        $to = $this->parseDate($validated['to'] ?? null, endOfDay: true);

        $events = $this->publishedEvents($request)
            ->upcoming($from)
            ->when($to, fn (Builder $query, CarbonInterface $to) => $query
                ->where('starts_at', '<=', $to->copy()->setTimezone(config('app.timezone'))->toDateTimeString()))
            ->when($validated['category'] ?? null, fn (Builder $query, string $category) => $query
                ->whereHas('categories', fn (Builder $categories) => $categories
                    ->where('categories.slug', $category)
                    ->where('categories.type', CategoryType::Event)))
            ->with(['kommun', 'categories', 'businessLocation.business', 'slugs', 'media'])
            ->orderBy('starts_at')
            ->orderBy('id')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return EventResource::collection($events);
    }

    public function show(Request $request, string $site, string $slug): EventResource|JsonResponse
    {
        $event = $this->findBySlug(
            $this->publishedEvents($request)->with(['kommun', 'categories', 'businessLocation.business', 'slugs', 'media']),
            $slug,
            $request,
        );

        return $event instanceof JsonResponse ? $event : EventResource::make($event);
    }

    /** @return Builder<Event> */
    protected function publishedEvents(Request $request): Builder
    {
        return Event::query()
            ->published()
            ->whereIn('kommun_id', $this->kommunIds($request))
            ->withoutRecurrenceTemplates();
    }

    /** Datum eller tidpunkt i appens tidszon (ISO 8601 med offset respekteras). Ett rent datum som slutgräns gäller hela den dagen. */
    protected function parseDate(?string $value, bool $endOfDay = false): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $date = Carbon::parse($value, config('app.timezone'));

        return $endOfDay && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $date->endOfDay() : $date;
    }
}
