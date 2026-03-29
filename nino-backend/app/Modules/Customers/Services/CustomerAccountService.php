<?php

namespace App\Modules\Customers\Services;

use App\Models\User;
use App\Modules\Community\Services\CommunityOnboardingService;
use App\Modules\IAM\Services\CustomerPasswordSetupService;
use App\Modules\Notifications\Services\NotificationTriggerService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class CustomerAccountService
{
    public function __construct(
        protected CommunityOnboardingService $communityOnboardingService,
        protected CustomerPasswordSetupService $passwordSetupService,
        protected NotificationTriggerService $notificationTriggerService,
    ) {}

    /**
     * Automatically create a customer account after a guest checkout.
     * Generates a random secure password and signals a welcome/setup email to be sent.
     *
     * @param array<string, string>|string $email The guest email or checkout identity payload.
     * @param string|null $firstName The customer first name.
     * @param string|null $lastName The customer last name.
     * @return User The newly created user instance.
     */
    public function createFromCheckout(array|string $email, ?string $firstName = null, ?string $lastName = null): User
    {
        [$email, $firstName, $lastName] = $this->normalizeCheckoutIdentity($email, $firstName, $lastName);

        $existing = User::where('email', $email)->first();
        if ($existing) {
            if (! $existing->isCustomer()) {
                throw new InvalidArgumentException('Checkout auto-account generation can only reuse existing customer identities.');
            }

            $this->communityOnboardingService->inviteNewCustomerToDefaultGroup($existing, 'checkout_existing_customer');

            return $existing;
        }

        // Generate a random secure password (never sent in plain text)
        $randomPassword = Str::random(40);

        $customer = User::create([
            'email' => $email,
            'password' => Hash::make($randomPassword),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => trim("{$firstName} {$lastName}"),
            'type' => 'customer',
            'status' => 'active',
            'community_auto_invite_to_default_group' => true,
        ]);

        $this->communityOnboardingService->inviteNewCustomerToDefaultGroup($customer, 'checkout_auto_create');

        try {
            $this->notificationTriggerService->welcome(
                $customer->email,
                $customer->full_name !== '' ? $customer->full_name : ($customer->name ?? 'Customer'),
                $customer->id,
            );
        } catch (Throwable $exception) {
            Log::warning('Customer welcome notification dispatch failed after checkout auto-creation.', [
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'error' => $exception->getMessage(),
            ]);
        }

        try {
            $this->passwordSetupService->dispatchSetupLink($customer, 'checkout_auto_create');
        } catch (Throwable $exception) {
            Log::warning('Customer password setup link dispatch failed after checkout auto-creation.', [
                'customer_id' => $customer->id,
                'email' => $customer->email,
                'error' => $exception->getMessage(),
            ]);
        }

        Log::info("Auto-created customer account from checkout for: {$email}. Password setup link dispatched.");

        return $customer;
    }

    /**
     * @param array<string, string>|string $email
     * @return array{0:string,1:string,2:string}
     */
    protected function normalizeCheckoutIdentity(array|string $email, ?string $firstName, ?string $lastName): array
    {
        if (is_array($email)) {
            return [
                Str::lower(trim((string) ($email['email'] ?? ''))),
                trim((string) ($email['first_name'] ?? '')),
                trim((string) ($email['last_name'] ?? '')),
            ];
        }

        return [
            Str::lower(trim($email)),
            trim((string) $firstName),
            trim((string) $lastName),
        ];
    }
}
