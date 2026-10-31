<?php

namespace App\Filament\Actions;

use App\Enums\RevisionableType;
use App\Models\Revision;
use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\View\View;

/** Tabellknapp som visar en revisions diff mot nuvarande rad i en modal. */
class RevisionDiffAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'diff';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Jämför');
        $this->icon(Heroicon::OutlinedArrowsRightLeft);
        $this->modalHeading(fn (Revision $record): string => static::headingFor($record));
        $this->modalContent(fn (Revision $record): View => view('filament.revisions.diff', ['rows' => $record->diff()]));
        $this->modalSubmitAction(false);
        $this->modalCancelActionLabel('Stäng');
        $this->modalWidth(Width::FourExtraLarge);
    }

    public static function headingFor(Revision $revision): string
    {
        $type = RevisionableType::tryFrom($revision->revisionable_type)?->getLabel() ?? $revision->revisionable_type;

        return $type.' – '.($revision->revisionable?->revisionTitle() ?? 'raderat innehåll');
    }
}
