<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('shipping_carriers')
            ->where('provider', 'sendit')
            ->orderBy('id')
            ->get(['id', 'settings'])
            ->each(function (object $carrier) use ($now): void {
                $settings = json_decode($carrier->settings ?: '[]', true);
                $settings = is_array($settings) ? $settings : [];

                if (filled(data_get($settings, 'webhook_secret'))) {
                    return;
                }

                $settings['webhook_secret'] = Str::random(40);

                DB::table('shipping_carriers')
                    ->where('id', $carrier->id)
                    ->update([
                        'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE),
                        'updated_at' => $now,
                    ]);
            });
    }

    public function down(): void
    {
        DB::table('shipping_carriers')
            ->where('provider', 'sendit')
            ->orderBy('id')
            ->get(['id', 'settings'])
            ->each(function (object $carrier): void {
                $settings = json_decode($carrier->settings ?: '[]', true);
                $settings = is_array($settings) ? $settings : [];

                if (! array_key_exists('webhook_secret', $settings)) {
                    return;
                }

                unset($settings['webhook_secret']);

                DB::table('shipping_carriers')
                    ->where('id', $carrier->id)
                    ->update([
                        'settings' => json_encode($settings, JSON_UNESCAPED_UNICODE),
                        'updated_at' => now(),
                    ]);
            });
    }
};
