<?php

namespace App\Modules\IAM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Audit\Services\AuditLogger;
use App\Modules\IAM\Services\AdminHomeRouteService;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;

class TwoFactorSetupController extends Controller
{
    private const SESSION_KEY = 'security.two_factor_setup.password_confirmed_at';

    public function __construct(
        private readonly SettingsService $settings,
        private readonly AuditLogger $audit,
        private readonly AdminHomeRouteService $homeRoutes,
    ) {}

    public function show(Request $request)
    {
        $user = $this->staffUser($request);

        if (! $this->isEnforced()) {
            return redirect()->route($this->homeRoutes->routeNameFor($user));
        }

        if ($user->hasConfirmedTwoFactorAuthentication()) {
            return redirect()->intended(route($this->homeRoutes->routeNameFor($user)));
        }

        $canRevealSetup = $request->session()->has(self::SESSION_KEY);
        $qrCodeSvg = null;
        $recoveryCodes = [];
        $secretKey = null;

        if ($canRevealSetup && filled($user->two_factor_secret)) {
            $user->refresh();
            $qrCodeSvg = $user->twoFactorQrCodeSvg();
            $secretKey = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);
            $recoveryCodes = filled($user->two_factor_recovery_codes) ? $user->recoveryCodes() : [];
        }

        return view('admin.security.two-factor-setup', [
            'requiresPasswordConfirmation' => ! $canRevealSetup,
            'qrCodeSvg' => $qrCodeSvg,
            'recoveryCodes' => $recoveryCodes,
            'secretKey' => $secretKey,
        ]);
    }

    public function prepare(
        Request $request,
        EnableTwoFactorAuthentication $enableTwoFactorAuthentication,
    ): RedirectResponse {
        $user = $this->staffUser($request);

        if (! $this->isEnforced()) {
            return redirect()->route($this->homeRoutes->routeNameFor($user));
        }

        if ($user->hasConfirmedTwoFactorAuthentication()) {
            return redirect()->intended(route($this->homeRoutes->routeNameFor($user)));
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => __('The provided password does not match your current account password.'),
            ]);
        }

        if (blank($user->two_factor_secret)) {
            $enableTwoFactorAuthentication($user, true);
        }

        $request->session()->put(self::SESSION_KEY, now()->timestamp);

        return redirect()
            ->route('admin.security.two-factor.setup')
            ->with('success', 'Scan the QR code, store your recovery codes, and enter the generated code to finish setup.');
    }

    public function confirm(
        Request $request,
        ConfirmTwoFactorAuthentication $confirmTwoFactorAuthentication,
    ): RedirectResponse {
        $user = $this->staffUser($request);

        if (! $this->isEnforced()) {
            return redirect()->route($this->homeRoutes->routeNameFor($user));
        }

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        $confirmTwoFactorAuthentication($user, $validated['code']);

        $user->refresh();
        $request->session()->forget(self::SESSION_KEY);

        $this->audit->log(
            action: 'security.two_factor.confirmed',
            target: $user,
            newValues: [
                'two_factor_confirmed_at' => $user->two_factor_confirmed_at?->toAtomString(),
            ],
            notes: 'Staff completed two-factor enrollment',
            context: [
                'source' => 'two_factor_setup_controller',
                'enforced' => $this->isEnforced(),
            ],
        );

        return redirect()
            ->intended(route($this->homeRoutes->routeNameFor($user)))
            ->with('success', 'Two-factor authentication is now active for your account.');
    }

    private function isEnforced(): bool
    {
        return (bool) $this->settings->get('security', 'force_2fa', false);
    }

    private function staffUser(Request $request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->isStaff(), 403);

        return $user;
    }
}
