<?php

namespace App\Notifications;

use App\Models\Business;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Inbjudan till portalen. En ny användare får en länk där hen väljer lösenord; en befintlig
 * får en länk rakt in i verksamheten.
 */
class BusinessInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Business $business,
        public User $inviter,
        public string $url,
        public bool $isNewUser,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("{$this->inviter->name} har bjudit in dig till {$this->business->namn}")
            ->greeting('Hej!')
            ->line("{$this->inviter->name} har gett dig tillgång till {$this->business->namn} i portalen, där ni uppdaterar uppgifter, öppettider, lunchmeny, evenemang och erbjudanden.");

        if ($this->isNewUser) {
            return $mail
                ->line('Ett konto har skapats åt dig. Välj ett lösenord för att komma igång.')
                ->action('Välj lösenord', $this->url)
                ->line('Länken är giltig i en begränsad tid. Går den ut kan du begära en ny via "Glömt lösenord" på inloggningssidan.');
        }

        return $mail
            ->action('Öppna portalen', $this->url)
            ->line('Du loggar in med ditt vanliga konto.');
    }
}
