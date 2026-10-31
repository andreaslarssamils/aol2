<?php

namespace App\Filament\Portal\Tenancy;

use App\Enums\CategoryType;
use App\Filament\Forms\Components\PendingImagesUpload;
use App\Filament\Forms\Components\RichText;
use App\Filament\Portal\Concerns\SubmitsRevisions;
use App\Filament\Portal\Schemas\Components\PendingRevisionBanner;
use App\Filament\Portal\Schemas\PortalFields;
use App\Filament\Schemas\Components\LocaleTabs;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Tenancy\EditTenantProfile;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Verksamheten (tenant-profilen): namn, beskrivning, kontakt, kategorier och bilder. Sparas
 * som revision; bilder hamnar i "pending" tills redaktionen godkänt. kind, managed_by och
 * auto_approve sätts bara i /admin.
 */
class EditVerksamhet extends EditTenantProfile
{
    use SubmitsRevisions;

    protected static ?string $slug = 'verksamhet';

    public static function getLabel(): string
    {
        return 'Verksamhet';
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                PendingRevisionBanner::make(),
                Section::make('Verksamhet')
                    ->schema([
                        TextInput::make('namn')
                            ->label('Namn')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('epost')
                            ->label('E-post')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('webbplats')
                            ->label('Webbplats')
                            ->url()
                            ->maxLength(255),
                        PortalFields::categories(CategoryType::Business),
                    ])
                    ->columns(2),
                LocaleTabs::make()
                    ->localeSchema(fn (string $locale): array => [
                        RichText::make("beskrivning.{$locale}")
                            ->label('Beskrivning'),
                    ]),
                Section::make('Bilder')
                    ->schema([
                        PendingImagesUpload::make(),
                    ]),
            ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['categories'] = $this->tenant->categories()->pluck('categories.id')->all();

        return $data;
    }
}
