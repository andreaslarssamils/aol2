<?php

namespace App\Filament\Portal\Concerns;

use App\Actions\Revisions\SubmitRevision;
use App\Enums\RevisionStatus;
use App\Models\Revision;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Sparregeln i portalen: Edit- och Create-sidor skriver aldrig direkt till innehållsraden utan
 * skickar formulärets data till SubmitRevision. Relationer som Filament sparar själv (väntande
 * bilder, öppettider) hanteras av respektive komponent och omfattas inte.
 */
trait SubmitsRevisions
{
    protected ?Revision $submittedRevision = null;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $this->submitRevision($record, $this->revisionPayload($data, $record));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        return $this->submitRevision(new ($this->getModel()), $this->revisionPayload($data, null));
    }

    /**
     * Fälten som ska föreslås. Create-sidor lägger till ägarlänken (business_id eller
     * business_location_id) här, eftersom tenant-scopingen inte skriver den åt oss.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function revisionPayload(array $data, ?Model $record): array
    {
        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function submitRevision(Model $model, array $payload): Model
    {
        try {
            $this->submittedRevision = app(SubmitRevision::class)->handle($model, $payload, Filament::auth()->user());
        } catch (ValidationException $exception) {
            // Form requestens fel ska landa på fälten i formuläret, inte som ett 500.
            throw ValidationException::withMessages(
                collect($exception->errors())->mapWithKeys(fn (array $messages, string $field): array => ["data.{$field}" => $messages])->all(),
            );
        }

        if ($model->exists) {
            $model->refresh();
        }

        return $model;
    }

    protected function getSavedNotification(): ?Notification
    {
        return $this->submissionNotification();
    }

    protected function getCreatedNotification(): ?Notification
    {
        return $this->submissionNotification();
    }

    protected function submissionNotification(): Notification
    {
        if ($this->submittedRevision?->status === RevisionStatus::Approved) {
            return Notification::make()
                ->success()
                ->title('Publicerad')
                ->body('Verksamhetens ändringar godkänns automatiskt, så den här syns direkt.');
        }

        return Notification::make()
            ->success()
            ->title('Skickad för granskning')
            ->body('Redaktionen granskar ändringen innan den publiceras. Du ser status under Mina ändringar.');
    }
}
