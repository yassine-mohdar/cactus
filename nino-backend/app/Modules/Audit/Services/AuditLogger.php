<?php

namespace App\Modules\Audit\Services;

use App\Modules\Audit\Models\AuditLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * @var array<int, string>
     */
    private const REDACTED_KEYS = [
        'password',
        'password_confirmation',
        'secret',
        'secret_key',
        'api_key',
        'client_secret',
        'token',
        'access_token',
        'refresh_token',
        'auth_token',
        'webhook_secret',
        'private_key',
        'publishable_key',
        'access_key',
        'merchant_key',
        'hash_key',
        'store_key',
        'signing_key',
        'signature',
        'passphrase',
        'remember_token',
    ];

    public function log(
        string $action,
        ?Model $target = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $notes = null,
        array $context = [],
        ?Authenticatable $actor = null,
        ?string $targetLabel = null,
    ): AuditLog {
        $actor ??= Auth::user();

        $sanitizedOldValues = $oldValues === null ? null : $this->sanitizePayload($oldValues);
        $sanitizedNewValues = $newValues === null ? null : $this->sanitizePayload($newValues);

        if (is_array($sanitizedOldValues) && is_array($sanitizedNewValues)) {
            [$sanitizedOldValues, $sanitizedNewValues] = $this->extractDiff($sanitizedOldValues, $sanitizedNewValues);
        }

        return AuditLog::create([
            'user_id' => $this->resolveActorId($actor),
            'actor_type' => $actor ? $actor::class : null,
            'actor_name' => $this->resolveActorName($actor),
            'actor_email' => $this->resolveActorEmail($actor),
            'action' => $action,
            'auditable_type' => $target ? $target::class : null,
            'auditable_id' => $target?->getKey(),
            'target_label' => $targetLabel ?? $this->resolveTargetLabel($target),
            'old_values' => $sanitizedOldValues,
            'new_values' => $sanitizedNewValues,
            'context' => array_merge($this->requestContext(), $this->sanitizePayload($context)),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'notes' => $notes,
        ]);
    }

    private function resolveActorId(?Authenticatable $actor): mixed
    {
        if ($actor === null) {
            return null;
        }

        return method_exists($actor, 'getAuthIdentifier')
            ? $actor->getAuthIdentifier()
            : null;
    }

    private function resolveActorName(?Authenticatable $actor): ?string
    {
        if ($actor === null) {
            return null;
        }

        $name = data_get($actor, 'name') ?? data_get($actor, 'full_name');

        return is_string($name) && $name !== '' ? $name : null;
    }

    private function resolveActorEmail(?Authenticatable $actor): ?string
    {
        if ($actor === null) {
            return null;
        }

        $email = data_get($actor, 'email');

        return is_string($email) && $email !== '' ? $email : null;
    }

    private function resolveTargetLabel(?Model $target): ?string
    {
        if ($target === null) {
            return null;
        }

        $candidate = collect([
            $target->getAttribute('name'),
            $target->getAttribute('title'),
            $target->getAttribute('reference_number'),
            $target->getAttribute('code'),
            $target->getAttribute('slug'),
            $target->getAttribute('email'),
            $target->getAttribute('provider'),
            $target->getAttribute('gateway_id'),
            $target->getAttribute('group'),
        ])->first(fn ($value) => filled($value));

        if (filled($candidate)) {
            return sprintf('%s: %s', class_basename($target), $candidate);
        }

        return sprintf('%s #%s', class_basename($target), $target->getKey());
    }

    private function requestContext(): array
    {
        $request = app()->bound('request') ? request() : null;

        if (! $request instanceof Request) {
            return [];
        }

        $route = $request->route();

        return array_filter([
            'request_id' => $request->headers->get('X-Request-Id'),
            'route_name' => $route?->getName(),
            'route_uri' => method_exists($route, 'uri') ? $route->uri() : null,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function extractDiff(array $oldValues, array $newValues): array
    {
        $oldChanges = [];
        $newChanges = [];

        foreach (array_unique(array_merge(array_keys($oldValues), array_keys($newValues))) as $key) {
            $hasOld = array_key_exists($key, $oldValues);
            $hasNew = array_key_exists($key, $newValues);

            if ($hasOld && $hasNew && is_array($oldValues[$key]) && is_array($newValues[$key])) {
                [$nestedOld, $nestedNew] = $this->extractDiff($oldValues[$key], $newValues[$key]);

                if ($nestedOld !== [] || $nestedNew !== []) {
                    $oldChanges[$key] = $nestedOld;
                    $newChanges[$key] = $nestedNew;
                }

                continue;
            }

            if ($hasOld && $hasNew && $oldValues[$key] === $newValues[$key]) {
                continue;
            }

            if ($hasOld) {
                $oldChanges[$key] = $oldValues[$key];
            }

            if ($hasNew) {
                $newChanges[$key] = $newValues[$key];
            }
        }

        return [$oldChanges, $newChanges];
    }

    /**
     * @param  mixed  $payload
     * @return mixed
     */
    private function sanitizePayload(mixed $payload): mixed
    {
        if (! is_array($payload)) {
            return $payload;
        }

        $sanitized = [];

        foreach ($payload as $key => $value) {
            if (is_string($key) && $this->shouldRedact($key)) {
                $sanitized[$key] = filled($value) ? '[REDACTED]' : $value;
                continue;
            }

            $sanitized[$key] = is_array($value)
                ? $this->sanitizePayload($value)
                : $value;
        }

        return $sanitized;
    }

    private function shouldRedact(string $key): bool
    {
        $normalized = strtolower($key);

        if (in_array($normalized, self::REDACTED_KEYS, true)) {
            return true;
        }

        return Arr::first(
            self::REDACTED_KEYS,
            fn (string $candidate) => str_contains($normalized, $candidate)
        ) !== null;
    }
}
