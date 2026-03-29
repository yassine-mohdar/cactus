<?php

namespace App\Modules\Shipping\Services;

use App\Modules\Settings\Services\SettingsService;

class ShippingSettingsService
{
    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function defaultMethodCode(): string
    {
        return trim((string) $this->settings->get('shipping', 'default_method_code', 'standard')) ?: 'standard';
    }

    public function defaultShippingCost(): float
    {
        return max(0, (float) $this->settings->get('shipping', 'default_shipping_cost', 45.0));
    }

    public function freeShippingThreshold(): float
    {
        return max(0, (float) $this->settings->get('shipping', 'free_shipping_threshold', 500.0));
    }

    public function defaultCarrierName(): string
    {
        return trim((string) $this->settings->get('shipping', 'default_carrier_name', 'Amana')) ?: 'Amana';
    }

    public function defaultEstimatedDays(): string
    {
        return trim((string) $this->settings->get('shipping', 'default_estimated_days', '2-4 business days')) ?: '2-4 business days';
    }

    /**
     * @return array<int, string>
     */
    public function carrierDirectory(): array
    {
        $raw = (string) $this->settings->get(
            'shipping',
            'carrier_directory',
            'Amana, DHL, Chronopost, FedEx, UPS',
        );

        return collect(explode(',', $raw))
            ->map(fn (string $carrier) => trim($carrier))
            ->filter()
            ->values()
            ->all();
    }

    public function trackingRequiredOnDispatch(): bool
    {
        return (bool) $this->settings->get('shipping', 'tracking_required_on_dispatch', false);
    }

    public function trackingUrlTemplate(): ?string
    {
        $template = trim((string) $this->settings->get('shipping', 'tracking_url_template', ''));

        return $template !== '' ? $template : null;
    }

    public function buildTrackingUrl(string $trackingNumber): ?string
    {
        $template = $this->trackingUrlTemplate();

        if (! $template) {
            return null;
        }

        return str_replace('{tracking_number}', urlencode($trackingNumber), $template);
    }
}
