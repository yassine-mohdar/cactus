<?php

namespace App\Modules\IAM\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StaffAccessSetupNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $setupUrl,
        public readonly int $expiryMinutes,
        public readonly string $source = 'staff_access_setup',
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
            ->subject('Set up your NinoWorld staff access')
            ->greeting("Hello {$displayName},")
            ->line('A secure access setup link has been generated for your NinoWorld operations account.')
            ->line('Use it to set a password and activate your staff access securely.')
            ->action('Set staff password', $this->setupUrl)
            ->line("This setup link expires in {$this->expiryMinutes} minutes.")
            ->line('If you did not expect this email, please contact your administrator.');
    }
}
