<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AppSettingService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = DB::table('app_settings')->where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        if ($setting->value === null) {
            return null;
        }

        $decoded = json_decode($setting->value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $setting->value;
    }

    public function set(string $key, mixed $value): void
    {
        $encodedValue = is_string($value) ? $value : json_encode($value);
        $now = now();

        $exists = $this->has($key);
        if ($exists) {
            DB::table('app_settings')->where('key', $key)->update([
                'value' => $encodedValue,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('app_settings')->insert([
                'key' => $key,
                'value' => $encodedValue,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function has(string $key): bool
    {
        return DB::table('app_settings')->where('key', $key)->exists();
    }

    public function remove(string $key): void
    {
        DB::table('app_settings')->where('key', $key)->delete();
    }

    public function all(): array
    {
        $settings = DB::table('app_settings')->get();
        $result = [];

        foreach ($settings as $setting) {
            if ($setting->value === null) {
                $result[$setting->key] = null;
            } else {
                $decoded = json_decode($setting->value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $result[$setting->key] = $decoded;
                } else {
                    $result[$setting->key] = $setting->value;
                }
            }
        }

        return $result;
    }
}
