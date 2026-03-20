<?php

namespace App\Modules\IAM\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffResetPasswordNotification extends Notification implements ShouldQueue
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
        $displayName = $notifiable->name ?? 'team member';

        return (new MailMessage)
            ->subject('Reset your NinoWorld staff password')
            ->greeting("Hello {$displayName},")
            ->line('We received a request to reset the password for your NinoWorld staff account.')
            ->action('Reset staff password', $this->resetUrl)
            ->line("This secure reset link expires in {$this->expiryMinutes} minutes.")
            ->line('If you did not request this reset, please contact an administrator.');
    }
}
