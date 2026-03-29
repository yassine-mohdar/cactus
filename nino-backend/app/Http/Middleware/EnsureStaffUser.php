<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->resolveUser($request);

        if (! $user instanceof User) {
            return $this->unauthenticatedResponse($request);
        }

        if (! $user->isStaff()) {
            return $this->forbiddenResponse($request);
        }

        return $next($request);
    }

    private function resolveUser(Request $request): ?User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            $user = $request->user('sanctum');

            if ($user instanceof User) {
                $request->setUserResolver(static fn (): User => $user);
            }
        }

        return $user instanceof User ? $user : null;
    }

    private function unauthenticatedResponse(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return redirect()->route('login');
    }

    private function forbiddenResponse(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'This route is restricted to staff users.'], 403);
        }

        return redirect()->route('customer.account.home');
    }
}
