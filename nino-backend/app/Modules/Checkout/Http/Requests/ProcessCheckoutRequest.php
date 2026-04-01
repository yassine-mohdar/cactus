<?php

namespace App\Modules\Checkout\Http\Requests;

use App\Modules\Customers\Models\Address;
use App\Modules\Payments\Services\PaymentMethodAvailabilityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        // Core differences based on authentication state
        $rules = [
            'payment_method' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! app(PaymentMethodAvailabilityService::class)->resolveByCode((string) $value)) {
                        $fail('The selected payment method is not available.');
                    }
                },
            ],
            'shipping_method_id' => ['nullable', 'integer', Rule::exists('shipping_methods', 'id')],
        ];

        if (!$user) {
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

        if ($user) {
            $userId = $user->id;
            $requiresShippingPayload = ! $this->filled('shipping_address_id');
            $requiresBillingPayload = ! $this->filled('billing_address_id');

            $rules['shipping_address'] = 'nullable|array|required_without:shipping_address_id';
            $rules['billing_address'] = 'nullable|array|required_without:billing_address_id';
            $rules['shipping_address_id'] = [
                'nullable',
                'integer',
                'required_without:shipping_address',
                Rule::exists('addresses', 'id')->where(fn ($query) => $query
                    ->where('user_id', $userId)
                    ->where('type', Address::TYPE_SHIPPING)),
            ];
            $rules['billing_address_id'] = [
                'nullable',
                'integer',
                'required_without:billing_address',
                Rule::exists('addresses', 'id')->where(fn ($query) => $query
                    ->where('user_id', $userId)
                    ->where('type', Address::TYPE_BILLING)),
            ];

            foreach ($addressRules as $key => $rule) {
                if ($requiresShippingPayload) {
                    $rules["shipping_address.{$key}"] = $rule;
                }

                if ($requiresBillingPayload) {
                    $rules["billing_address.{$key}"] = $rule;
                }
            }

            return $rules;
        }

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
            'shipping_address_id.exists' => 'The selected shipping address is not available for this customer.',
            'billing_address_id.exists' => 'The selected billing address is not available for this customer.',
        ];
    }
}
