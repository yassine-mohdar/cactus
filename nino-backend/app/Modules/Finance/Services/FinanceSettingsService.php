<?php

namespace App\Modules\Finance\Services;

use App\Modules\Settings\Services\SettingsService;

class FinanceSettingsService
{
    private const ROUNDING_STRATEGIES = [
        'none',
        'nearest_0_05',
        'nearest_0_10',
        'nearest_whole',
    ];

    public function __construct(
        private readonly SettingsService $settings,
    ) {}

    public function baseCurrency(): string
    {
        $configured = (string) $this->settings->get(
            'finance',
            'base_currency',
            $this->settings->get('general', 'currency', config('finance.base_currency', 'MAD'))
        );

        $normalized = strtoupper(trim($configured));

        return preg_match('/^[A-Z]{3}$/', $normalized) === 1
            ? $normalized
            : strtoupper((string) config('finance.base_currency', 'MAD'));
    }

    public function multiCurrencyEnabled(): bool
    {
        return (bool) $this->settings->get('finance', 'multi_currency_enabled', config('finance.multi_currency_enabled', false));
    }

    /**
     * @return array<int, string>
     */
    public function supportedCurrencies(): array
    {
        $base = $this->baseCurrency();
        $configured = (string) $this->settings->get('finance', 'supported_currencies', $base);

        $currencies = collect(preg_split('/[\s,]+/', strtoupper($configured), -1, PREG_SPLIT_NO_EMPTY))
            ->filter(fn (string $currency): bool => preg_match('/^[A-Z]{3}$/', $currency) === 1)
            ->unique()
            ->values()
            ->all();

        if ($currencies === []) {
            return [$base];
        }

        if (! in_array($base, $currencies, true)) {
            array_unshift($currencies, $base);
        }

        return array_values(array_unique($currencies));
    }

    /**
     * @return array<int, string>
     */
    public function activeCurrencies(): array
    {
        return $this->multiCurrencyEnabled()
            ? $this->supportedCurrencies()
            : [$this->baseCurrency()];
    }

    public function conversionAdjustmentPercent(): float
    {
        return round((float) $this->settings->get(
            'finance',
            'conversion_adjustment_percent',
            config('finance.conversion_adjustment_percent', 0.0)
        ), 2);
    }

    public function priceRoundingStrategy(): string
    {
        $configured = (string) $this->settings->get(
            'finance',
            'price_rounding_strategy',
            config('finance.price_rounding_strategy', 'none')
        );

        return in_array($configured, self::ROUNDING_STRATEGIES, true)
            ? $configured
            : 'none';
    }

    public function defaultTaxRate(): float
    {
        return round((float) $this->settings->get(
            'finance',
            'default_tax_rate',
            config('finance.default_tax_rate', 20.0)
        ), 2);
    }

    public function defaultPaymentTermsDays(): int
    {
        return max(0, (int) $this->settings->get(
            'finance',
            'default_payment_terms_days',
            config('finance.default_payment_terms_days', 0)
        ));
    }
}
