<?php

namespace App\Filament\Pages;

use App\Actions\Revisions\ApproveRevision;
use App\Actions\Revisions\RejectRevision;
use App\Enums\RevisionableType;
use App\Models\Article;
use App\Models\Business;
use App\Models\BusinessLocation;
use App\Models\Event;
use App\Models\Kommun;
use App\Models\LunchMenu;
use App\Models\Offer;
use App\Models\Page as ContentPage;
use App\Models\Revision;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use UnitEnum;

/**
 * Granskningskön: väntande revisioner över alla innehållstyper, med diff mot nuvarande rad
 * och knappar för att godkänna eller avslå. Bara admin och redaktör.
 */
class GranskningsKo extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $slug = 'granskningsko';

    protected static ?string $title = 'Granskningskö';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static string|UnitEnum|null $navigationGroup = 'Innehåll';

    protected static ?int $navigationSort = 0;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([User::ROLE_ADMIN, User::ROLE_REDAKTOR]) ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = Revision::query()->pending()->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                EmbeddedTable::make(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => Revision::query()
                ->pending()
                ->with([
                    'submitter',
                    'revisionable' => fn (MorphTo $morphTo) => $morphTo->morphWith([
                        Business::class => ['primaryLocation.kommun'],
                        BusinessLocation::class => ['business', 'kommun'],
                        LunchMenu::class => ['businessLocation.business', 'businessLocation.kommun'],
                        Offer::class => ['business.primaryLocation.kommun'],
                        Article::class => ['kommun'],
                        Event::class => ['kommun'],
                        ContentPage::class => ['kommun'],
                    ]),
                ]))
            ->columns([
                TextColumn::make('revisionable_type')
                    ->label('Typ')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => RevisionableType::tryFrom($state)?->getLabel() ?? $state),
                TextColumn::make('title')
                    ->label('Innehåll')
                    ->state(fn (Revision $record): string => $record->revisionable?->revisionTitle() ?? 'Raderat innehåll'),
                TextColumn::make('kommun')
                    ->label('Kommun')
                    ->state(fn (Revision $record): ?string => $record->revisionable?->revisionKommun()?->namn)
                    ->placeholder('–'),
                TextColumn::make('submitter.name')
                    ->label('Inskickad av')
                    ->placeholder('–'),
                TextColumn::make('created_at')
                    ->label('Inskickad')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('revisionable_type')
                    ->label('Typ')
                    ->options(RevisionableType::class),
                SelectFilter::make('kommun')
                    ->label('Kommun')
                    ->options(fn (): array => Kommun::query()->orderBy('namn')->pluck('namn', 'id')->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $query, int|string $kommunId): Builder => $query
                            ->whereHasMorph('revisionable', '*', fn (Builder $revisionable) => $revisionable->inKommun((int) $kommunId)),
                    )),
            ])
            ->recordActions([
                $this->diffAction(),
                $this->approveAction(),
                $this->rejectAction(),
            ])
            // Kö: äldst först.
            ->defaultSort(fn (Builder $query): Builder => $query->orderBy('created_at')->orderBy('id'))
            ->emptyStateHeading('Inget väntar på granskning')
            ->emptyStateDescription('Ändringar från verksamhetsägare hamnar här tills de godkänts eller avslagits.');
    }

    protected function diffAction(): Action
    {
        return Action::make('diff')
            ->label('Jämför')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->modalHeading(fn (Revision $record): string => $this->headingFor($record))
            ->modalContent(fn (Revision $record): View => $this->diffView($record))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Stäng')
            ->modalWidth(Width::FourExtraLarge);
    }

    protected function approveAction(): Action
    {
        return Action::make('approve')
            ->label('Godkänn')
            ->icon(Heroicon::OutlinedCheck)
            ->color('success')
            ->modalHeading(fn (Revision $record): string => 'Godkänn: '.$this->headingFor($record))
            ->modalDescription('Ändringen skrivs till innehållet, publiceras och väntande bilder blir synliga.')
            ->modalContent(fn (Revision $record): View => $this->diffView($record))
            ->schema([
                Textarea::make('comment')
                    ->label('Kommentar (valfri)')
                    ->rows(2),
            ])
            ->modalSubmitActionLabel('Godkänn')
            ->modalWidth(Width::FourExtraLarge)
            ->successNotificationTitle('Ändringen är godkänd och publicerad')
            ->action(function (Revision $record, array $data, Action $action, ApproveRevision $approveRevision): void {
                $comment = trim((string) ($data['comment'] ?? ''));

                $approveRevision->handle($record, auth()->user(), $comment === '' ? null : $comment);
                $action->success();
            });
    }

    protected function rejectAction(): Action
    {
        return Action::make('reject')
            ->label('Avslå')
            ->icon(Heroicon::OutlinedXMark)
            ->color('danger')
            ->modalHeading(fn (Revision $record): string => 'Avslå: '.$this->headingFor($record))
            ->modalDescription('Innehållet rörs inte, men väntande bilder raderas. Kommentaren visas för den som skickade in ändringen.')
            ->modalContent(fn (Revision $record): View => $this->diffView($record))
            ->schema([
                Textarea::make('comment')
                    ->label('Kommentar')
                    ->required()
                    ->rows(3),
            ])
            ->modalSubmitActionLabel('Avslå')
            ->modalWidth(Width::FourExtraLarge)
            ->successNotificationTitle('Ändringen är avslagen')
            ->action(function (Revision $record, array $data, Action $action, RejectRevision $rejectRevision): void {
                $rejectRevision->handle($record, auth()->user(), trim($data['comment']));
                $action->success();
            });
    }

    protected function diffView(Revision $revision): View
    {
        return view('filament.revisions.diff', ['rows' => $revision->diff()]);
    }

    protected function headingFor(Revision $revision): string
    {
        $type = RevisionableType::tryFrom($revision->revisionable_type)?->getLabel() ?? $revision->revisionable_type;

        return $type.' – '.($revision->revisionable?->revisionTitle() ?? 'raderat innehåll');
    }
}
