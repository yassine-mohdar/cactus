<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Checkout\Models\CartItem;
use App\Modules\Checkout\Services\CartService;
use App\Modules\Finance\Services\FinanceSettingsService;
use App\Modules\Notifications\Channels\EmailChannel;
use App\Modules\Notifications\Models\IntegrationSetting;
use App\Modules\Notifications\Services\NotificationSettingsService;
use App\Modules\Shipping\Services\ShippingSettingsService;
use App\Modules\Settings\Models\Setting;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SettingsFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_settings_page_renders_grouped_sections_for_an_authorized_manager(): void
    {
        $manager = $this->makeSettingsManager();

        $response = $this->actingAs($manager)->get(route('admin.settings.index', ['tab' => 'general']));

        $response->assertOk();
        $response->assertSee('Settings');
        $response->assertSee('General');
        $response->assertSee('SEO');
        $response->assertSee('Mail');
        $response->assertSee('Notifications');
        $response->assertSee('Shipping');
        $response->assertSee('Security');
        $response->assertSee('Finance');
        $response->assertSee('Website Information');
        $response->assertSee('Company Information');
        $response->assertSee('Contact Information');
        $response->assertSee('Branding Basics');
    }

    public function test_seo_settings_page_renders_grouped_seo_sections(): void
    {
        $manager = $this->makeSettingsManager();

        $response = $this->actingAs($manager)->get(route('admin.settings.index', ['tab' => 'seo']));

        $response->assertOk();
        $response->assertSee('SEO Defaults');
        $response->assertSee('Analytics Integrations');
        $response->assertSee('Search Verification');
        $response->assertSee('Advertising Pixels');
    }

    public function test_notifications_settings_page_renders_channel_sender_and_template_sections(): void
    {
        $manager = $this->makeSettingsManager();

        $response = $this->actingAs($manager)->get(route('admin.settings.index', ['tab' => 'notifications']));

        $response->assertOk();
        $response->assertSee('Notifications');
        $response->assertSee('Channel Controls');
        $response->assertSee('Sender Defaults');
        $response->assertSee('Template Globals');
        $response->assertSee('Queue & Retry');
        $response->assertSee('Enable Email Notifications');
        $response->assertSee('Notification Sender Name');
        $response->assertSee('Template Footer Text');
    }

    public function test_shipping_settings_page_renders_shipping_sections(): void
    {
        $manager = $this->makeSettingsManager();

        $response = $this->actingAs($manager)->get(route('admin.settings.index', ['tab' => 'shipping']));

        $response->assertOk();
        $response->assertSee('Shipping &amp; Fulfillment', false);
        $response->assertSee('Shipping Defaults');
        $response->assertSee('Carrier &amp; Method Foundation', false);
        $response->assertSee('Tracking Defaults');
        $response->assertSee('Default Checkout Shipping Method');
        $response->assertSee('Fallback Tracking URL Template');
    }

    public function test_settings_service_returns_grouped_typed_values_and_invalidates_group_cache(): void
    {
        $settings = app(SettingsService::class);

        $settings->set('security', 'force_2fa', true, 'boolean');
        $settings->set('security', 'session_lifetime', 90, 'integer');

        $this->assertSame([
            'force_2fa' => true,
            'session_lifetime' => 90,
        ], $settings->group('security'));

        Setting::query()
            ->where('group', 'security')
            ->where('key', 'session_lifetime')
            ->update([
                'value' => Setting::prepareValue(45, 'integer'),
            ]);

        $this->assertSame(90, $settings->get('security', 'session_lifetime'));

        $settings->invalidateGroup('security');

        $this->assertSame(45, $settings->get('security', 'session_lifetime'));
    }

    public function test_settings_service_can_store_encrypted_secret_values(): void
    {
        $settings = app(SettingsService::class);

        $settings->set('mail', 'smtp_password', 'super-secret-value', 'secret', true);

        $stored = Setting::query()
            ->where('group', 'mail')
            ->where('key', 'smtp_password')
            ->firstOrFail();

        $this->assertTrue($stored->is_encrypted);
        $this->assertNotSame('super-secret-value', $stored->value);
        $this->assertSame('super-secret-value', $settings->get('mail', 'smtp_password'));
    }

    public function test_settings_update_validates_section_rules_before_persisting_changes(): void
    {
        $manager = $this->makeSettingsManager();

        $response = $this->actingAs($manager)->from(route('admin.settings.index', ['tab' => 'security']))
            ->put(route('admin.settings.update'), [
                'group' => 'security',
                'force_2fa' => 'not-a-boolean',
                'session_lifetime' => 2,
                'password_min_length' => 40,
            ]);

        $response->assertRedirect(route('admin.settings.index', ['tab' => 'security']));
        $response->assertSessionHasErrors([
            'force_2fa',
            'session_lifetime',
            'password_min_length',
        ]);

        $this->assertDatabaseMissing('settings', [
            'group' => 'security',
            'key' => 'session_lifetime',
        ]);
    }

    public function test_general_settings_update_persists_website_company_contact_and_branding_fields(): void
    {
        $manager = $this->makeSettingsManager();

        $response = $this->actingAs($manager)->put(route('admin.settings.update'), [
            'group' => 'general',
            'site_name' => 'NinoWorld Ops',
            'site_tagline' => 'Precision retail operations.',
            'site_url' => 'https://ops.ninoworld.ma',
            'company_name' => 'NinoWorld Operations',
            'company_legal_name' => 'NinoWorld Operations SARL AU',
            'company_registration_id' => 'RC Casa 778899',
            'company_email' => 'contact@ninoworld.ma',
            'support_email' => 'support@ninoworld.ma',
            'company_phone' => '+212522000001',
            'company_address' => 'Casablanca Finance City',
            'brand_logo_url' => 'https://cdn.ninoworld.ma/logo.svg',
            'brand_favicon_url' => 'https://cdn.ninoworld.ma/favicon.ico',
            'brand_primary_color' => '#17302A',
            'brand_accent_color' => '#C18B3B',
            'timezone' => 'Africa/Casablanca',
            'currency' => 'MAD',
        ]);

        $response->assertRedirect(route('admin.settings.index', ['tab' => 'general']));

        $this->assertSame('NinoWorld Ops', app(SettingsService::class)->get('general', 'site_name'));
        $this->assertSame('NinoWorld Operations SARL AU', app(SettingsService::class)->get('general', 'company_legal_name'));
        $this->assertSame('support@ninoworld.ma', app(SettingsService::class)->get('general', 'support_email'));
        $this->assertSame('#17302A', app(SettingsService::class)->get('general', 'brand_primary_color'));
        $this->assertSame('https://cdn.ninoworld.ma/favicon.ico', app(SettingsService::class)->get('general', 'brand_favicon_url'));
    }

    public function test_seo_settings_update_persists_analytics_verification_and_pixel_fields(): void
    {
        $manager = $this->makeSettingsManager();

        $response = $this->actingAs($manager)->put(route('admin.settings.update'), [
            'group' => 'seo',
            'meta_title' => 'NinoWorld - Morocco',
            'meta_description' => 'Operations-first kawaii commerce.',
            'google_analytics' => 'G-ABC123XYZ9',
            'google_tag_manager' => 'GTM-ABC1234',
            'google_verification' => 'google-site-verification=token-123',
            'facebook_pixel' => '123456789012345',
            'tiktok_pixel' => 'TT-99887766',
        ]);

        $response->assertRedirect(route('admin.settings.index', ['tab' => 'seo']));

        $settings = app(SettingsService::class);

        $this->assertSame('G-ABC123XYZ9', $settings->get('seo', 'google_analytics'));
        $this->assertSame('GTM-ABC1234', $settings->get('seo', 'google_tag_manager'));
        $this->assertSame('google-site-verification=token-123', $settings->get('seo', 'google_verification'));
        $this->assertSame('123456789012345', $settings->get('seo', 'facebook_pixel'));
        $this->assertSame('TT-99887766', $settings->get('seo', 'tiktok_pixel'));
        $this->assertSame('NinoWorld - Morocco', $settings->get('seo', 'meta_title'));
        $this->assertSame('Operations-first kawaii commerce.', $settings->get('seo', 'meta_description'));
    }

    public function test_security_settings_page_renders_security_sections(): void
    {
        $manager = $this->makeSettingsManager();

        $response = $this->actingAs($manager)->get(route('admin.settings.index', ['tab' => 'security']));

        $response->assertOk();
        $response->assertSee('Access Security');
        $response->assertSee('Password Policy');
        $response->assertSee('Force Staff/Admin Two-Factor Authentication');
        $response->assertSee('Minimum Password Length');
        $response->assertSee('Require Uppercase and Lowercase Letters');
        $response->assertSee('Require Numbers');
        $response->assertSee('Require Symbols');
        $response->assertSee('Security Alerts');
        $response->assertSee('Primary Security Alert Email');
    }

    public function test_notification_settings_persist_channel_sender_and_template_global_fields(): void
    {
        $manager = $this->makeSettingsManager();

        $response = $this->actingAs($manager)->put(route('admin.settings.update'), [
            'group' => 'notifications',
            'email_enabled' => '1',
            'sms_enabled' => '0',
            'whatsapp_enabled' => '1',
            'sender_name' => 'NinoWorld Operations',
            'footer_text' => 'Thank you for choosing NinoWorld.',
            'signature' => 'Customer Experience Team',
            'queue_name' => 'notifications-critical',
            'max_attempts' => 5,
            'retry_base_delay_minutes' => 4,
        ]);

        $response->assertRedirect(route('admin.settings.index', ['tab' => 'notifications']));

        $settings = app(SettingsService::class);
        $notificationSettings = app(NotificationSettingsService::class);

        $this->assertTrue($settings->get('notifications', 'email_enabled'));
        $this->assertFalse($settings->get('notifications', 'sms_enabled'));
        $this->assertTrue($settings->get('notifications', 'whatsapp_enabled'));
        $this->assertSame('NinoWorld Operations', $settings->get('notifications', 'sender_name'));
        $this->assertSame('Thank you for choosing NinoWorld.', $settings->get('notifications', 'footer_text'));
        $this->assertSame('Customer Experience Team', $settings->get('notifications', 'signature'));
        $this->assertSame('notifications-critical', $settings->get('notifications', 'queue_name'));
        $this->assertSame(5, $settings->get('notifications', 'max_attempts'));
        $this->assertSame(4, $settings->get('notifications', 'retry_base_delay_minutes'));
        $this->assertFalse($notificationSettings->isChannelEnabled(\App\Modules\Notifications\Enums\NotificationChannel::SMS));
        $this->assertSame('NinoWorld Operations', $notificationSettings->senderName());
        $this->assertSame('Customer Experience Team', $notificationSettings->templateSignature());
        $this->assertSame('notifications-critical', $notificationSettings->queueName());
        $this->assertSame(5, $notificationSettings->maxAttempts());
        $this->assertSame(4, $notificationSettings->retryBaseDelayMinutes());
    }

    public function test_shipping_settings_persist_and_apply_to_shipping_service_defaults(): void
    {
        $manager = $this->makeSettingsManager();

        $response = $this->actingAs($manager)->put(route('admin.settings.update'), [
            'group' => 'shipping',
            'default_method_code' => 'express-priority',
            'default_shipping_cost' => '65',
            'free_shipping_threshold' => '750',
            'default_carrier_name' => 'DHL',
            'default_estimated_days' => '1-2 business days',
            'carrier_directory' => 'DHL, Amana, FedEx',
            'tracking_required_on_dispatch' => '1',
            'tracking_url_template' => 'https://track.example.test/{tracking_number}',
        ]);

        $response->assertRedirect(route('admin.settings.index', ['tab' => 'shipping']));

        $shippingSettings = app(ShippingSettingsService::class);
        $cart = app(CartService::class)->getCart(null, 'shipping-settings-cart');
        $product = Product::create([
            'name' => 'Shipping Settings Test Product',
            'slug' => 'shipping-settings-test-product',
            'type' => 'simple',
            'status' => 'published',
            'sku' => 'SHIP-SETTINGS-001',
            'price' => 100,
            'quantity' => 10,
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $summary = app(CartService::class)->getSummary($cart->fresh());

        $this->assertSame('express-priority', $shippingSettings->defaultMethodCode());
        $this->assertSame(65.0, $shippingSettings->defaultShippingCost());
        $this->assertSame(750.0, $shippingSettings->freeShippingThreshold());
        $this->assertSame('DHL', $shippingSettings->defaultCarrierName());
        $this->assertSame('1-2 business days', $shippingSettings->defaultEstimatedDays());
        $this->assertSame(['DHL', 'Amana', 'FedEx'], $shippingSettings->carrierDirectory());
        $this->assertTrue($shippingSettings->trackingRequiredOnDispatch());
        $this->assertSame('https://track.example.test/ABC-123', $shippingSettings->buildTrackingUrl('ABC-123'));
        $this->assertSame(65.0, $summary['totals']['shipping_estimate']);
        $this->assertSame(750.0, $summary['free_shipping_progress']['threshold']);
    }

    public function test_security_settings_update_persists_two_factor_and_password_policy_fields(): void
    {
        $manager = $this->makeSettingsManager();

        $response = $this->actingAs($manager)->put(route('admin.settings.update'), [
            'group' => 'security',
            'force_2fa' => '1',
            'session_lifetime' => 75,
            'password_min_length' => 12,
            'password_require_mixed_case' => '1',
            'password_require_numbers' => '1',
            'password_require_symbols' => '1',
        ]);

        $response->assertRedirect(route('admin.settings.index', ['tab' => 'security']));

        $settings = app(SettingsService::class);

        $this->assertTrue($settings->get('security', 'force_2fa'));
        $this->assertSame(75, $settings->get('security', 'session_lifetime'));
        $this->assertSame(12, $settings->get('security', 'password_min_length'));
        $this->assertTrue($settings->get('security', 'password_require_mixed_case'));
        $this->assertTrue($settings->get('security', 'password_require_numbers'));
        $this->assertTrue($settings->get('security', 'password_require_symbols'));
    }

    public function test_finance_settings_page_renders_currency_sections(): void
    {
        $manager = $this->makeSettingsManager();

        $response = $this->actingAs($manager)->get(route('admin.settings.index', ['tab' => 'finance']));

        $response->assertOk();
        $response->assertSee('Currency Defaults');
        $response->assertSee('Currency Strategy');
        $response->assertSee('Base Currency');
        $response->assertSee('Enable Multi-Currency Foundation');
        $response->assertSee('Supported Currencies (CSV)');
        $response->assertSee('Conversion Rules');
        $response->assertSee('Conversion Adjustment Percent');
        $response->assertSee('Financial Defaults');
        $response->assertSee('Default Tax Rate (%)');
    }

    public function test_security_alert_and_finance_settings_persist_through_the_settings_center(): void
    {
        $manager = $this->makeSettingsManager();

        $this->actingAs($manager)->put(route('admin.settings.update'), [
            'group' => 'security',
            'force_2fa' => '0',
            'session_lifetime' => 60,
            'password_min_length' => 10,
            'password_require_mixed_case' => '1',
            'password_require_numbers' => '1',
            'password_require_symbols' => '0',
            'security_alert_email' => 'security@ninoworld.ma',
            'security_alert_recipients' => 'ops@ninoworld.ma, cto@ninoworld.ma',
        ])->assertRedirect(route('admin.settings.index', ['tab' => 'security']));

        $this->actingAs($manager)->put(route('admin.settings.update'), [
            'group' => 'finance',
            'base_currency' => 'EUR',
            'multi_currency_enabled' => '1',
            'supported_currencies' => 'EUR, MAD, USD',
            'conversion_adjustment_percent' => '2.5',
            'price_rounding_strategy' => 'nearest_0_05',
            'default_tax_rate' => '20',
            'default_payment_terms_days' => '14',
        ])->assertRedirect(route('admin.settings.index', ['tab' => 'finance']));

        $settings = app(SettingsService::class);
        $finance = app(FinanceSettingsService::class);

        $this->assertSame('security@ninoworld.ma', $settings->get('security', 'security_alert_email'));
        $this->assertSame('ops@ninoworld.ma, cto@ninoworld.ma', $settings->get('security', 'security_alert_recipients'));
        $this->assertSame('EUR', $settings->get('finance', 'base_currency'));
        $this->assertTrue($settings->get('finance', 'multi_currency_enabled'));
        $this->assertSame('EUR, MAD, USD', $settings->get('finance', 'supported_currencies'));
        $this->assertSame(2.5, (float) $settings->get('finance', 'conversion_adjustment_percent'));
        $this->assertSame('nearest_0_05', $settings->get('finance', 'price_rounding_strategy'));
        $this->assertSame(20.0, (float) $settings->get('finance', 'default_tax_rate'));
        $this->assertSame(14, (int) $settings->get('finance', 'default_payment_terms_days'));
        $this->assertSame('EUR', $finance->baseCurrency());
        $this->assertSame(['EUR', 'MAD', 'USD'], $finance->activeCurrencies());
        $this->assertSame(2.5, $finance->conversionAdjustmentPercent());
        $this->assertSame('nearest_0_05', $finance->priceRoundingStrategy());
        $this->assertSame(20.0, $finance->defaultTaxRate());
        $this->assertSame(14, $finance->defaultPaymentTermsDays());
    }

    public function test_general_settings_apply_locale_and_timezone_at_runtime(): void
    {
        app(SettingsService::class)->setMany('general', [
            ['key' => 'locale', 'value' => 'fr', 'type' => 'string'],
            ['key' => 'timezone', 'value' => 'Africa/Casablanca', 'type' => 'string'],
        ]);

        Route::middleware('web')->get('/__test/settings/general-runtime', function () {
            return response()->json([
                'locale' => app()->getLocale(),
                'timezone' => config('app.timezone'),
            ]);
        });

        $response = $this->get('/__test/settings/general-runtime');

        $response->assertOk();
        $response->assertJson([
            'locale' => 'fr',
            'timezone' => 'Africa/Casablanca',
        ]);
    }

    public function test_finance_settings_apply_base_currency_and_currency_list_at_runtime(): void
    {
        app(SettingsService::class)->setMany('finance', [
            ['key' => 'base_currency', 'value' => 'EUR', 'type' => 'string'],
            ['key' => 'multi_currency_enabled', 'value' => true, 'type' => 'boolean'],
            ['key' => 'supported_currencies', 'value' => 'EUR, MAD, USD', 'type' => 'string'],
            ['key' => 'conversion_adjustment_percent', 'value' => 3.5, 'type' => 'number'],
            ['key' => 'price_rounding_strategy', 'value' => 'nearest_0_10', 'type' => 'string'],
            ['key' => 'default_tax_rate', 'value' => 20, 'type' => 'number'],
            ['key' => 'default_payment_terms_days', 'value' => 7, 'type' => 'integer'],
        ]);

        Route::middleware('web')->get('/__test/settings/finance-runtime', function () {
            return response()->json([
                'base_currency' => config('finance.base_currency'),
                'multi_currency_enabled' => config('finance.multi_currency_enabled'),
                'supported_currencies' => config('finance.supported_currencies'),
                'conversion_adjustment_percent' => config('finance.conversion_adjustment_percent'),
                'price_rounding_strategy' => config('finance.price_rounding_strategy'),
                'default_tax_rate' => config('finance.default_tax_rate'),
                'default_payment_terms_days' => config('finance.default_payment_terms_days'),
                'app_currency' => config('app.currency'),
            ]);
        });

        $response = $this->get('/__test/settings/finance-runtime');

        $response->assertOk();
        $response->assertJson([
            'base_currency' => 'EUR',
            'multi_currency_enabled' => true,
            'supported_currencies' => ['EUR', 'MAD', 'USD'],
            'conversion_adjustment_percent' => 3.5,
            'price_rounding_strategy' => 'nearest_0_10',
            'default_tax_rate' => 20.0,
            'default_payment_terms_days' => 7,
            'app_currency' => 'EUR',
        ]);
    }

    public function test_cart_service_uses_the_finance_base_currency_for_new_carts(): void
    {
        app(SettingsService::class)->set('finance', 'base_currency', 'EUR', 'string');

        $cart = app(CartService::class)->getCart(null, 'finance-cart-001');

        $this->assertSame('EUR', $cart->currency);
    }

    public function test_mail_tab_seeds_default_integrations_for_smtp_twilio_and_whatsapp(): void
    {
        $manager = $this->makeSettingsManager();

        $this->assertSame(0, IntegrationSetting::query()->count());

        $response = $this->actingAs($manager)->get(route('admin.settings.index', ['tab' => 'mail']));

        $response->assertOk();
        $response->assertSee('SMTP Email Server');
        $response->assertSee('Twilio SMS');
        $response->assertSee('WhatsApp Business API');
        $this->assertSame(3, IntegrationSetting::query()->count());
    }

    public function test_integration_settings_validate_required_credentials_when_enabling_providers(): void
    {
        $manager = $this->makeSettingsManager();

        IntegrationSetting::ensureDefaultsExist();
        $twilio = IntegrationSetting::query()->where('provider', 'twilio')->firstOrFail();

        $response = $this->actingAs($manager)
            ->from(route('admin.settings.index', ['tab' => 'mail']))
            ->put(route('admin.notifications.integrations.update', $twilio), [
                'is_enabled' => '1',
                'credentials' => [
                    'account_sid' => '',
                    'auth_token' => '',
                    'from_number' => '',
                ],
            ]);

        $response->assertRedirect(route('admin.settings.index', ['tab' => 'mail']));
        $response->assertSessionHasErrors([
            'credentials.account_sid',
            'credentials.auth_token',
            'credentials.from_number',
        ]);
    }

    public function test_enabled_integration_settings_can_be_saved_for_smtp_twilio_and_whatsapp(): void
    {
        $manager = $this->makeSettingsManager();

        IntegrationSetting::ensureDefaultsExist();

        $smtp = IntegrationSetting::query()->where('provider', 'smtp')->firstOrFail();
        $twilio = IntegrationSetting::query()->where('provider', 'twilio')->firstOrFail();
        $whatsApp = IntegrationSetting::query()->where('provider', 'whatsapp_api')->firstOrFail();

        $this->actingAs($manager)->put(route('admin.notifications.integrations.update', $smtp), [
            'is_enabled' => '1',
            'credentials' => [
                'host' => 'smtp.postmarkapp.com',
                'port' => '587',
                'username' => 'postmark-user',
                'password' => 'smtp-secret',
                'encryption' => 'tls',
                'from_address' => 'ops@ninoworld.ma',
                'from_name' => 'NinoWorld Ops',
            ],
        ])->assertRedirect(route('admin.notifications.integrations.index'));

        $this->actingAs($manager)->put(route('admin.notifications.integrations.update', $twilio), [
            'is_enabled' => '1',
            'credentials' => [
                'account_sid' => 'AC123456789',
                'auth_token' => 'twilio-secret',
                'from_number' => '+212600000111',
            ],
        ])->assertRedirect(route('admin.notifications.integrations.index'));

        $this->actingAs($manager)->put(route('admin.notifications.integrations.update', $whatsApp), [
            'is_enabled' => '1',
            'credentials' => [
                'access_token' => 'wa-secret-token',
                'phone_number_id' => '123456789',
                'business_account_id' => '987654321',
                'api_version' => 'v18.0',
            ],
        ])->assertRedirect(route('admin.notifications.integrations.index'));

        $smtp->refresh();
        $twilio->refresh();
        $whatsApp->refresh();

        $this->assertTrue($smtp->is_enabled);
        $this->assertSame('smtp.postmarkapp.com', $smtp->getCredential('host'));
        $this->assertTrue($twilio->is_enabled);
        $this->assertSame('AC123456789', $twilio->getCredential('account_sid'));
        $this->assertTrue($whatsApp->is_enabled);
        $this->assertSame('123456789', $whatsApp->getCredential('phone_number_id'));
    }

    public function test_safe_connection_test_reports_success_for_valid_provider_settings(): void
    {
        $manager = $this->makeSettingsManager();

        IntegrationSetting::ensureDefaultsExist();
        $smtp = IntegrationSetting::query()->where('provider', 'smtp')->firstOrFail();
        $smtp->update([
            'is_enabled' => true,
            'credentials' => [
                'host' => 'smtp.postmarkapp.com',
                'port' => '587',
                'username' => 'mailer-user',
                'password' => 'mailer-secret',
                'encryption' => 'tls',
                'from_address' => 'ops@ninoworld.ma',
                'from_name' => 'NinoWorld Ops',
            ],
        ]);

        $response = $this->actingAs($manager)->get(route('admin.notifications.integrations.test', $smtp));

        $response->assertRedirect(route('admin.notifications.integrations.index'));
        $response->assertSessionHas('success');
    }

    public function test_safe_connection_test_reports_warning_for_disabled_provider(): void
    {
        $manager = $this->makeSettingsManager();

        IntegrationSetting::ensureDefaultsExist();
        $smtp = IntegrationSetting::query()->where('provider', 'smtp')->firstOrFail();
        $smtp->update([
            'is_enabled' => false,
            'credentials' => [
                'host' => 'smtp.postmarkapp.com',
                'port' => '587',
                'username' => 'mailer-user',
                'password' => 'mailer-secret',
                'encryption' => 'tls',
                'from_address' => 'ops@ninoworld.ma',
                'from_name' => 'NinoWorld Ops',
            ],
        ]);

        $response = $this->actingAs($manager)->get(route('admin.notifications.integrations.test', $smtp));

        $response->assertRedirect(route('admin.notifications.integrations.index'));
        $response->assertSessionHas('warning');
    }

    public function test_smtp_integration_settings_are_applied_to_runtime_email_configuration(): void
    {
        IntegrationSetting::ensureDefaultsExist();

        $smtp = IntegrationSetting::query()->where('provider', 'smtp')->firstOrFail();
        $smtp->update([
            'is_enabled' => true,
            'credentials' => [
                'host' => 'smtp.postmarkapp.com',
                'port' => '587',
                'username' => 'mailer-user',
                'password' => 'mailer-secret',
                'encryption' => 'tls',
                'from_address' => 'ops@ninoworld.ma',
                'from_name' => 'NinoWorld Ops',
            ],
        ]);

        $channel = app(EmailChannel::class);
        $method = new ReflectionMethod($channel, 'applySmtpRuntimeConfig');
        $method->setAccessible(true);
        $method->invoke($channel, $smtp);

        $this->assertSame('smtp.postmarkapp.com', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertSame('ops@ninoworld.ma', config('mail.from.address'));
        $this->assertSame('NinoWorld Ops', config('mail.from.name'));
        $this->assertTrue($channel->isConfigured());
    }

    public function test_bulk_setting_updates_invalidate_cached_group_once_written(): void
    {
        Cache::flush();

        $settings = app(SettingsService::class);

        $settings->setMany('general', [
            [
                'key' => 'site_name',
                'value' => 'NinoWorld',
                'type' => 'string',
            ],
            [
                'key' => 'currency',
                'value' => 'MAD',
                'type' => 'string',
            ],
        ]);

        $this->assertSame('NinoWorld', $settings->get('general', 'site_name'));

        Setting::query()
            ->where('group', 'general')
            ->where('key', 'site_name')
            ->update(['value' => 'Changed Directly']);

        $this->assertSame('NinoWorld', $settings->get('general', 'site_name'));

        $settings->setMany('general', [
            [
                'key' => 'site_name',
                'value' => 'NinoWorld Reloaded',
                'type' => 'string',
            ],
        ]);

        $this->assertSame('NinoWorld Reloaded', $settings->get('general', 'site_name'));
    }

    private function makeSettingsManager(): User
    {
        Permission::findOrCreate('settings.manage', 'web');

        $user = User::factory()->create([
            'type' => 'staff',
            'status' => 'active',
            'organization_scope' => 'platform',
        ]);

        $user->givePermissionTo('settings.manage');

        return $user;
    }
}
