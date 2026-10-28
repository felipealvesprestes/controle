<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;

/**
 * Invitation email; the link is built from APP_URL, never from the request host (A-04).
 */
class InvitationNotification extends Notification
{
    public function __construct(public readonly string $token) {}

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $url = rtrim(Config::string('app.url'), '/').route('invitation.show', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ], absolute: false);

        return (new MailMessage)
            ->subject(__('You have been invited to :app', ['app' => Config::string('app.name')]))
            ->line(__('An administrator created an account for you. Set your password to start.'))
            ->action(__('Set my password'), $url)
            ->line(__('This link works once and expires in 72 hours.'));
    }
}
