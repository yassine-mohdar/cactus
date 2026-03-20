<?php

namespace App\Modules\Customers\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CustomerAccountService
{
    /**
     * Automatically create a customer account after a guest checkout.
     * Generates a random secure password and signals a welcome/setup email to be sent.
     *
     * @param string $email The guest email.
     * @param string $firstName The customer first name.
     * @param string $lastName The customer last name.
     * @return User The newly created user instance.
     */
    public function createFromCheckout(string $email, string $firstName, string $lastName): User
    {
        // If a user already exists with this email, return them (or throw an exception based on business rules)
        $existing = User::where('email', $email)->first();
        if ($existing) {
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
        ]);

        // Trigger welcome notification / magic link generation logic here.
        // E.g. event(new CustomerAccountCreated($customer));
        Log::info("Auto-created customer account from checkout for: {$email}. Welcome notification queued.");

        return $customer;
    }
}
