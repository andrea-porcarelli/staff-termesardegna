<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SetPasswordLinkNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $email = $notifiable->getEmailForPasswordReset();
        $url = url(route('password.set', ['token' => $this->token], false))
            .'?email='.urlencode($email);

        $expireMinutes = (int) config(
            'auth.passwords.'.config('auth.defaults.passwords').'.expire',
            60
        );

        $appName = config('app.name');

        return (new MailMessage)
            ->subject('Imposta la tua password — '.$appName)
            ->greeting('Ciao '.($notifiable->name ?: '').'!')
            ->line('Hai ricevuto questa email per impostare la tua password di accesso a '.$appName.'.')
            ->action('Imposta password', $url)
            ->line('Il link è valido per '.$expireMinutes.' minuti.')
            ->line('Se non hai richiesto questa email, puoi ignorarla in sicurezza.');
    }
}
