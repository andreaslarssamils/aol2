<?php

namespace App\Notifications;

use App\Enums\ClaimStatus;
use App\Models\BusinessClaim;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Mejl till den som gjort anspråk på en verksamhet när redaktionen godkänt eller avslagit det. */
class BusinessClaimDecided extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BusinessClaim $claim) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $business = $this->claim->business;
        $name = $notifiable instanceof User ? $notifiable->name : null;

        if ($this->claim->status === ClaimStatus::Approved) {
            return (new MailMessage)
                ->subject("Du har nu tillgång till {$business->namn}")
                ->greeting($name ? "Hej {$name}!" : 'Hej!')
                ->line("Redaktionen har godkänt ditt anspråk på {$business->namn}. Du kan nu uppdatera uppgifter, öppettider, lunchmeny, evenemang och erbjudanden i portalen.")
                ->action('Öppna portalen', Filament::getPanel(User::PANEL_PORTAL)->getUrl($business))
                ->line('Ändringar du gör granskas av redaktionen innan de publiceras.');
        }

        return (new MailMessage)
            ->subject("Ditt anspråk på {$business->namn} avslogs")
            ->greeting($name ? "Hej {$name}!" : 'Hej!')
            ->line("Redaktionen kunde inte bekräfta att du företräder {$business->namn}, och anspråket har därför avslagits.")
            ->line('Tror du att det är ett misstag? Svara på det här mejlet så tittar vi igen.');
    }
}
