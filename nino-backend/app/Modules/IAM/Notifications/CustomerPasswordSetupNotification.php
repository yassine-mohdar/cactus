<?php

namespace App\Modules\IAM\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerPasswordSetupNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $setupUrl,
        public readonly int $expiryMinutes,
        public readonly string $source = 'checkout_created',
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
            ->subject('Set up your NinoWorld account')
            ->greeting("Hello {$displayName},")
            ->line('Your checkout created a customer account for faster order tracking and future purchases.')
            ->line('Finish setting up your account password to access your customer area securely.')
            ->action('Set your password', $this->setupUrl)
            ->line("This secure setup link expires in {$this->expiryMinutes} minutes.")
            ->line('If you did not expect this message, you can safely ignore it.');
    }
}
