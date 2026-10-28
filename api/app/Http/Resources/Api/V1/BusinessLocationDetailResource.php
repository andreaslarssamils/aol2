<?php

namespace App\Http\Resources\Api\V1;

use App\Models\BusinessLocation;
use App\Models\LunchMenu;
use App\Models\OpeningHourException;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

/**
 * Detaljsidan: listraden plus hela företaget, veckans öppettider (avvikelser inräknade),
 * aktuell lunchmeny och kommande evenemang på platsen.
 *
 * @mixin BusinessLocation
 */
class BusinessLocationDetailResource extends BusinessLocationResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'business' => BusinessResource::make($this->business),
            'opening_hours' => $this->openingHoursThisWeek(),
            'lunch_menu' => $this->whenLoaded('currentLunchMenu', fn (LunchMenu $menu) => LunchMenuResource::make($menu)),
            'upcoming_events' => EventResource::collection($this->whenLoaded('upcomingEvents')),
        ];
    }

    /**
     * Måndag till söndag innevarande vecka i visningstidszonen. En avvikelse ersätter ordinarie tider för sin dag.
     *
     * @return list<array{date: string, weekday: int, closed: bool, is_exception: bool, note: ?string, intervals: list<array{opens: string, closes: string}>}>
     */
    protected function openingHoursThisWeek(): array
    {
        $monday = now()->setTimezone(config('app.display_timezone'))->startOfWeek(CarbonInterface::MONDAY);
        $week = [];

        for ($weekday = 0; $weekday < 7; $weekday++) {
            $date = $monday->copy()->addDays($weekday);
            $rows = $this->hoursOn($date);
            $exception = $rows->first();
            $intervals = $rows
                ->reject(fn ($hours): bool => $hours->closed || $hours->opens === null || $hours->closes === null)
                ->map(fn ($hours): array => ['opens' => substr($hours->opens, 0, 5), 'closes' => substr($hours->closes, 0, 5)])
                ->values()
                ->all();

            $week[] = [
                'date' => $date->toDateString(),
                'weekday' => $weekday,
                'closed' => $intervals === [],
                'is_exception' => $exception instanceof OpeningHourException,
                'note' => $exception instanceof OpeningHourException ? $exception->note : null,
                'intervals' => $intervals,
            ];
        }

        return $week;
    }
}
