<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Payments\Models\GatewaySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GatewaySettingsFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_gateway_settings_page_renders_cmi_and_payzone_configuration_fields(): void
    {
        $manager = $this->makeGatewayManager();

        GatewaySetting::query()->create([
            'gateway_id' => 'offline_transfer',
            'name' => 'Offline Bank Transfer',
            'is_enabled' => false,
            'mode' => 'test',
            'credentials' => [],
            'metadata' => [],
        ]);

        GatewaySetting::query()->create([
            'gateway_id' => 'stripe',
            'name' => 'Stripe',
            'is_enabled' => false,
            'mode' => 'test',
            'credentials' => [],
            'metadata' => [],
        ]);

        GatewaySetting::query()->create([
            'gateway_id' => 'cmi',
            'name' => 'CMI',
            'is_enabled' => false,
            'mode' => 'test',
            'credentials' => [],
            'metadata' => [],
        ]);

        GatewaySetting::query()->create([
            'gateway_id' => 'payzone',
            'name' => 'Payzone',
            'is_enabled' => false,
            'mode' => 'test',
            'credentials' => [],
            'metadata' => [],
        ]);

        $response = $this->actingAs($manager)->get(route('admin.gateways.index'));

        $response->assertOk();
        $response->assertSee('Store ID');
        $response->assertSee('Client ID');
        $response->assertSee('Hash Key');
        $response->assertSee('Terminal ID');
        $response->assertSee('Publishable Key');
        $response->assertSee('Webhook Secret');
        $response->assertSee('Merchant ID');
        $response->assertSee('API Key');
        $response->assertSee('Secret Key');
        $response->assertSee('Bank Name');
        $response->assertSee('IBAN');
        $response->assertSee('Payment Window');
        $response->assertSee('Checkout Title');
        $response->assertSee('Admin verification notes');
    }

    public function test_cmi_gateway_settings_require_provider_specific_fields_and_preserve_masked_hash_key(): void
    {
        $manager = $this->makeGatewayManager();

        $gateway = GatewaySetting::query()->create([
            'gateway_id' => 'cmi',
            'name' => 'CMI',
            'is_enabled' => false,
            'mode' => 'test',
            'credentials' => [
                'hash_key' => 'existing-cmi-secret',
            ],
            'metadata' => [
                'currency_code' => '504',
                'language' => 'fr',
            ],
        ]);

        $this->actingAs($manager)
            ->from(route('admin.gateways.index'))
            ->put(route('admin.gateways.update', $gateway), [
                'is_enabled' => '1',
                'mode' => 'live',
                'credentials' => [
                    'store_id' => '',
                    'client_id' => '',
                    'hash_key' => '',
                ],
                'metadata' => [
                    'currency_code' => 'MAD',
                    'language' => 'es',
                ],
            ])
            ->assertRedirect(route('admin.gateways.index'))
            ->assertSessionHasErrors([
                'credentials.store_id',
                'credentials.client_id',
                'metadata.currency_code',
                'metadata.language',
            ]);

        $this->actingAs($manager)
            ->put(route('admin.gateways.update', $gateway), [
                'is_enabled' => '1',
                'mode' => 'live',
                'credentials' => [
                    'store_id' => 'CMI-STORE-01',
                    'client_id' => 'CMI-CLIENT-77',
                    'hash_key' => '*******',
                    'terminal_id' => 'TERMINAL-01',
                ],
                'metadata' => [
                    'currency_code' => '504',
                    'language' => 'EN',
                ],
            ])
            ->assertRedirect(route('admin.gateways.index'));

        $gateway->refresh();

        $this->assertTrue($gateway->is_enabled);
        $this->assertSame('live', $gateway->mode);
        $this->assertSame('CMI-STORE-01', $gateway->getCredential('store_id'));
        $this->assertSame('CMI-CLIENT-77', $gateway->getCredential('client_id'));
        $this->assertSame('existing-cmi-secret', $gateway->getCredential('hash_key'));
        $this->assertSame('TERMINAL-01', $gateway->getCredential('terminal_id'));
        $this->assertSame('504', $gateway->metadata['currency_code'] ?? null);
        $this->assertSame('en', $gateway->metadata['language'] ?? null);
    }

    public function test_payzone_gateway_settings_require_provider_specific_fields_and_preserve_masked_secrets(): void
    {
        $manager = $this->makeGatewayManager();

        $gateway = GatewaySetting::query()->create([
            'gateway_id' => 'payzone',
            'name' => 'Payzone',
            'is_enabled' => false,
            'mode' => 'test',
            'credentials' => [
                'api_key' => 'existing-api-key',
                'secret_key' => 'existing-secret-key',
            ],
            'metadata' => [
                'currency' => 'MAD',
            ],
        ]);

        $this->actingAs($manager)
            ->from(route('admin.gateways.index'))
            ->put(route('admin.gateways.update', $gateway), [
                'is_enabled' => '1',
                'mode' => 'live',
                'credentials' => [
                    'merchant_id' => '',
                    'api_key' => '',
                    'secret_key' => '',
                ],
                'metadata' => [
                    'currency' => 'ma',
                ],
            ])
            ->assertRedirect(route('admin.gateways.index'))
            ->assertSessionHasErrors([
                'credentials.merchant_id',
                'metadata.currency',
            ]);

        $this->actingAs($manager)
            ->put(route('admin.gateways.update', $gateway), [
                'is_enabled' => '1',
                'mode' => 'live',
                'credentials' => [
                    'merchant_id' => 'PAYZONE-MERCHANT-01',
                    'api_key' => '*******',
                    'secret_key' => '*******',
                ],
                'metadata' => [
                    'currency' => 'eur',
                ],
            ])
            ->assertRedirect(route('admin.gateways.index'));

        $gateway->refresh();

        $this->assertTrue($gateway->is_enabled);
        $this->assertSame('live', $gateway->mode);
        $this->assertSame('PAYZONE-MERCHANT-01', $gateway->getCredential('merchant_id'));
        $this->assertSame('existing-api-key', $gateway->getCredential('api_key'));
        $this->assertSame('existing-secret-key', $gateway->getCredential('secret_key'));
        $this->assertSame('EUR', $gateway->metadata['currency'] ?? null);
    }

    public function test_stripe_gateway_settings_require_provider_specific_fields_and_preserve_masked_secrets(): void
    {
        $manager = $this->makeGatewayManager();

        $gateway = GatewaySetting::query()->create([
            'gateway_id' => 'stripe',
            'name' => 'Stripe',
            'is_enabled' => false,
            'mode' => 'test',
            'credentials' => [
                'secret_key' => 'existing-stripe-secret',
                'webhook_secret' => 'existing-webhook-secret',
            ],
            'metadata' => [
                'publishable_key' => 'pk_test_existing',
                'currency' => 'MAD',
            ],
        ]);

        $this->actingAs($manager)
            ->from(route('admin.gateways.index'))
            ->put(route('admin.gateways.update', $gateway), [
                'is_enabled' => '1',
                'mode' => 'sandbox',
                'credentials' => [
                    'secret_key' => '',
                    'webhook_secret' => '',
                ],
                'metadata' => [
                    'publishable_key' => '',
                    'currency' => 'ma',
                ],
            ])
            ->assertRedirect(route('admin.gateways.index'))
            ->assertSessionHasErrors([
                'mode',
                'metadata.publishable_key',
                'metadata.currency',
            ]);

        $this->actingAs($manager)
            ->put(route('admin.gateways.update', $gateway), [
                'is_enabled' => '1',
                'mode' => 'live',
                'credentials' => [
                    'secret_key' => '*******',
                    'webhook_secret' => '*******',
                ],
                'metadata' => [
                    'publishable_key' => 'pk_live_12345',
                    'currency' => 'usd',
                ],
            ])
            ->assertRedirect(route('admin.gateways.index'));

        $gateway->refresh();

        $this->assertTrue($gateway->is_enabled);
        $this->assertSame('live', $gateway->mode);
        $this->assertSame('existing-stripe-secret', $gateway->getCredential('secret_key'));
        $this->assertSame('existing-webhook-secret', $gateway->getCredential('webhook_secret'));
        $this->assertSame('pk_live_12345', $gateway->metadata['publishable_key'] ?? null);
        $this->assertSame('USD', $gateway->metadata['currency'] ?? null);
    }

    public function test_gateway_enable_toggle_persists_enabled_and_disabled_state(): void
    {
        $manager = $this->makeGatewayManager();

        $gateway = GatewaySetting::query()->create([
            'gateway_id' => 'offline_transfer',
            'name' => 'Offline Bank Transfer',
            'is_enabled' => false,
            'mode' => 'test',
            'credentials' => [],
            'metadata' => [
                'method_label' => 'Bank Transfer',
                'checkout_title' => 'Bank transfer instructions',
                'instructions' => 'Pay within 48 hours.',
            ],
        ]);

        $this->actingAs($manager)
            ->put(route('admin.gateways.update', $gateway), [
                'is_enabled' => '1',
                'mode' => 'live',
                'credentials' => [],
                'metadata' => [
                    'method_label' => 'Bank Transfer',
                    'checkout_title' => 'Bank transfer instructions',
                    'instructions' => 'Pay within 48 hours.',
                ],
            ])
            ->assertRedirect(route('admin.gateways.index'));

        $gateway->refresh();
        $this->assertTrue($gateway->is_enabled);

        $this->actingAs($manager)
            ->put(route('admin.gateways.update', $gateway), [
                'mode' => 'test',
                'credentials' => [],
                'metadata' => [
                    'method_label' => 'Bank Transfer',
                    'checkout_title' => 'Bank transfer instructions',
                    'instructions' => 'Pay within 48 hours.',
                ],
            ])
            ->assertRedirect(route('admin.gateways.index'));

        $gateway->refresh();
        $this->assertFalse($gateway->is_enabled);
    }

    public function test_offline_transfer_settings_persist_bank_transfer_defaults_and_mode(): void
    {
        $manager = $this->makeGatewayManager();

        $gateway = GatewaySetting::query()->create([
            'gateway_id' => 'offline_transfer',
            'name' => 'Offline Bank Transfer',
            'is_enabled' => false,
            'mode' => 'test',
            'credentials' => [],
            'metadata' => [],
        ]);

        $this->actingAs($manager)
            ->from(route('admin.gateways.index'))
            ->put(route('admin.gateways.update', $gateway), [
                'is_enabled' => '1',
                'mode' => 'live',
                'metadata' => [
                    'method_label' => '',
                    'checkout_title' => '',
                    'instructions' => '',
                    'payment_window_hours' => '0',
                ],
            ])
            ->assertRedirect(route('admin.gateways.index'))
            ->assertSessionHasErrors([
                'metadata.method_label',
                'metadata.checkout_title',
                'metadata.instructions',
                'metadata.payment_window_hours',
            ]);

        $this->actingAs($manager)
            ->put(route('admin.gateways.update', $gateway), [
                'is_enabled' => '1',
                'mode' => 'live',
                'metadata' => [
                    'method_label' => 'Manual Bank Transfer',
                    'checkout_title' => 'Pay by bank transfer',
                    'checkout_description' => 'Show customers the account details immediately after checkout.',
                    'instructions' => 'Please complete the transfer and send the receipt to finance.',
                    'admin_instructions' => 'Verify the transfer reference and receipt before marking paid.',
                    'bank_name' => 'Attijariwafa Bank',
                    'account_holder' => 'NinoWorld SARL AU',
                    'account_number' => '12345678901234',
                    'iban' => 'MA64001122334455667788990011',
                    'swift_code' => 'BCMAMAMC',
                    'payment_window_hours' => '72',
                    'reference_prefix' => 'NW',
                    'require_receipt' => '1',
                ],
            ])
            ->assertRedirect(route('admin.gateways.index'));

        $gateway->refresh();

        $this->assertTrue($gateway->is_enabled);
        $this->assertSame('live', $gateway->mode);
        $this->assertSame('Manual Bank Transfer', $gateway->metadata['method_label'] ?? null);
        $this->assertSame('Pay by bank transfer', $gateway->metadata['checkout_title'] ?? null);
        $this->assertSame('Show customers the account details immediately after checkout.', $gateway->metadata['checkout_description'] ?? null);
        $this->assertSame('Verify the transfer reference and receipt before marking paid.', $gateway->metadata['admin_instructions'] ?? null);
        $this->assertSame('Attijariwafa Bank', $gateway->metadata['bank_name'] ?? null);
        $this->assertSame('MA64001122334455667788990011', $gateway->metadata['iban'] ?? null);
        $this->assertSame(72, $gateway->metadata['payment_window_hours'] ?? null);
        $this->assertSame('NW', $gateway->metadata['reference_prefix'] ?? null);
        $this->assertTrue((bool) ($gateway->metadata['require_receipt'] ?? false));
    }

    private function makeGatewayManager(): User
    {
        Permission::findOrCreate('finance.manage_gateways', 'web');

        $user = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
            'organization_scope' => 'platform',
        ]);

        $user->givePermissionTo('finance.manage_gateways');

        return $user;
    }
}
