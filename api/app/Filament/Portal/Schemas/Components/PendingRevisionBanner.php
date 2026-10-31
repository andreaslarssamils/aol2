<?php

namespace App\Filament\Portal\Schemas\Components;

use App\Models\Revision;
use Closure;
use Filament\Actions\Action;
use Filament\Schemas\Components\Callout;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;

/**
 * Varning överst i portalens formulär när raden redan har en väntande revision: visar när och
 * av vem, länkar till diffen och säger att ett nytt inskick ersätter den. Syns inte annars.
 */
class PendingRevisionBanner extends Callout
{
    public const string HEADING = 'Det finns redan en väntande ändring för den här posten';

    public static function make(string|Htmlable|Closure|null $heading = null): static
    {
        return parent::make($heading ?? self::HEADING);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->warning();
        $this->columnSpanFull();
        $this->visible(fn (?Model $record): bool => static::pendingFor($record) !== null);
        $this->description(function (?Model $record): ?string {
            $revision = static::pendingFor($record);

            if ($revision === null) {
                return null;
            }

            $submitter = $revision->submitter?->name ?? 'okänd';

            return "Inskickad {$revision->created_at->format('Y-m-d H:i')} av {$submitter} och väntar på redaktionens granskning. Skickar du in igen ersätts den väntande ändringen av den nya.";
        });
        $this->actions([
            Action::make('pendingDiff')
                ->label('Visa väntande ändring')
                ->icon(Heroicon::OutlinedArrowsRightLeft)
                ->color('warning')
                ->link()
                ->modalHeading('Väntande ändring')
                ->modalDescription('Nuvarande uppgifter jämförda med det som väntar på granskning.')
                ->modalContent(fn (?Model $record): View => view('filament.revisions.diff', [
                    'rows' => static::pendingFor($record)?->diff() ?? [],
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Stäng')
                ->modalWidth(Width::FourExtraLarge),
        ]);
    }

    public static function pendingFor(?Model $record): ?Revision
    {
        if (! $record?->exists || ! method_exists($record, 'latestPendingRevision')) {
            return null;
        }

        return $record->latestPendingRevision()->with('submitter')->first();
    }
}
