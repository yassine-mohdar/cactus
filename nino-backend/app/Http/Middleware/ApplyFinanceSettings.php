<?php

namespace App\Http\Middleware;

use App\Modules\Finance\Services\FinanceSettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ApplyFinanceSettings
{
    public function __construct(
        private readonly FinanceSettingsService $financeSettings,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        try {
            config([
                'finance.base_currency' => $this->financeSettings->baseCurrency(),
                'finance.multi_currency_enabled' => $this->financeSettings->multiCurrencyEnabled(),
                'finance.supported_currencies' => $this->financeSettings->activeCurrencies(),
                'finance.conversion_adjustment_percent' => $this->financeSettings->conversionAdjustmentPercent(),
                'finance.price_rounding_strategy' => $this->financeSettings->priceRoundingStrategy(),
                'finance.default_tax_rate' => $this->financeSettings->defaultTaxRate(),
                'finance.default_payment_terms_days' => $this->financeSettings->defaultPaymentTermsDays(),
                'app.currency' => $this->financeSettings->baseCurrency(),
            ]);
        } catch (Throwable) {
            // Keep framework defaults when settings are not available yet.
        }

        return $next($request);
    }
}
