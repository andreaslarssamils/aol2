<?php

namespace App\Filament\Forms\Components;

use App\Enums\ContentStatus;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

/**
 * Status för innehåll. Redaktionen får publicera direkt – godkännandeflödet via revisions
 * gäller verksamhetsägare. Publiceras något utan datum stämplas published_at med nu.
 */
class ContentStatusSelect extends Select
{
    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'status');
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Status');
        $this->options(ContentStatus::class);
        $this->default(ContentStatus::Draft->value);
        $this->required();
        $this->live();

        $this->afterStateUpdated(static function (Set $set, Get $get, mixed $state): void {
            $status = $state instanceof ContentStatus ? $state : ContentStatus::tryFrom((string) $state);

            if ($status === ContentStatus::Published && blank($get('published_at'))) {
                $set('published_at', now()->toDateTimeString());
            }
        });
    }
}
