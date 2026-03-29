<?php

namespace App\Modules\IAM\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $resetUrl,
        public readonly int $expiryMinutes,
    ) {
        $this->afterCommit();
        $this->onQueue(config('performance.queues.notifications', 'notifications'));
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $name = trim((string) ($notifiable->first_name ?? '').' '.(string) ($notifiable->last_name ?? ''));
        $displayName = $name !== '' ? $name : ($notifiable->name ?? 'there');

        return (new MailMessage)
            ->subject('Reset your NinoWorld customer password')
            ->greeting("Hello {$displayName},")
            ->line('We received a request to reset your NinoWorld customer account password.')
            ->action('Reset customer password', $this->resetUrl)
            ->line("This secure reset link expires in {$this->expiryMinutes} minutes.")
            ->line('If you did not request a password reset, you can safely ignore this email.');
    }
}
