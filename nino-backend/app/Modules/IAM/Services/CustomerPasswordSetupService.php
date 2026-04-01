<?php

namespace App\Modules\IAM\Services;

use App\Models\User;
use App\Modules\IAM\Notifications\CustomerPasswordSetupNotification;
use App\Modules\Notifications\Services\NotificationTriggerService;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;

class CustomerPasswordSetupService
{
    public function __construct(
        private readonly NotificationTriggerService $notificationTriggerService,
    ) {}

    public function createSetupUrl(User $customer, string $source = 'checkout_created'): string
    {
        $this->ensureCustomerUser($customer);

        return URL::temporarySignedRoute(
            'customer.password.setup.show',
            now()->addMinutes($this->expiresInMinutes()),
            [
                'token' => Password::broker($this->broker())->createToken($customer),
                'email' => $customer->email,
                'source' => $source,
            ],
        );
    }

    public function dispatchSetupLink(User $customer, string $source = 'checkout_created'): string
    {
        $setupUrl = $this->createSetupUrl($customer, $source);

        $customer->notify(new CustomerPasswordSetupNotification(
            setupUrl: $setupUrl,
            expiryMinutes: $this->expiresInMinutes(),
            source: $source,
        ));

        $this->notificationTriggerService->passwordSetup(
            $customer->email,
            $customer->full_name !== '' ? $customer->full_name : ($customer->name ?? 'Customer'),
            $setupUrl,
            (int) ceil($this->expiresInMinutes() / 60),
            $customer->id,
        );

        return $setupUrl;
    }

    public function expiresInMinutes(): int
    {
        return max(1, (int) config('auth.passwords.'.$this->broker().'.expire', 60));
    }

    private function broker(): string
    {
        return (string) config('fortify.passwords', config('auth.defaults.passwords', 'users'));
    }

    private function ensureCustomerUser(User $customer): void
    {
        if (! $customer->isCustomer()) {
            throw new InvalidArgumentException('Password setup links can only be issued for customer accounts.');
        }
    }
}
