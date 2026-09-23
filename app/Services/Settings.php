<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Citirea și scrierea setărilor editabile din admin.
 *
 * Valorile se citesc pe fiecare pagină publică, deci stau în cache. Cache-ul se
 * golește la fiecare salvare; nu ne bazăm pe expirare, ca o corecție de ID să
 * fie vizibilă imediat.
 */
final class Settings
{
    private const CACHE_KEY = 'app_settings';

    /**
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, static fn (): array => AppSetting::query()
                ->pluck('value', 'key')
                ->all());
        } catch (Throwable) {
            // Setările nu au voie să doboare o pagină publică: dacă tabelul nu
            // există încă sau baza e inaccesibilă, mergem pe valori implicite.
            return [];
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key] ?? $default;
    }

    public static function string(string $key, string $default = ''): string
    {
        $value = self::get($key);

        return is_string($value) && trim($value) !== '' ? trim($value) : $default;
    }

    public static function boolean(string $key, bool $default = false): bool
    {
        $value = self::get($key);

        return is_bool($value) ? $value : $default;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            AppSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
