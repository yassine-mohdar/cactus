<?php

namespace App\Modules\Checkout\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Core differences based on authentication state
        $rules = [
            'payment_method' => 'required|string',
        ];

        if (!auth('sanctum')->check()) {
            $rules['cart_session_id'] = 'required|string';
            $rules['customer_email'] = 'required|email|unique:users,email'; 
            // Unique email means the guest hasn't created an account yet. 
            // If they have, we would instruct them to login.
            
            $rules['customer_first_name'] = 'required|string|max:255';
            $rules['customer_last_name'] = 'required|string|max:255';
        }

        // Shipping and Billing validation
        // They can provide a full array of data, or if authenticated, an ID.
        // For simplicity right now, we demand the explicit payload array to enforce data snapshot reliability.
        $addressRules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'postal_code' => 'required|string|max:50',
            'country' => 'required|string|size:2',
        ];

        // Flat array map for shipping
        foreach ($addressRules as $key => $rule) {
            $rules["shipping_address.{$key}"] = $rule;
            $rules["billing_address.{$key}"] = $rule;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'customer_email.unique' => 'An account already exists with this email address. Please log in to continue checkout.',
        ];
    }
}
