<?php

namespace App\Support\GeoRank;

use InvalidArgumentException;

class WireGuard
{
    public const FORBIDDEN_KEYS = [
        'cookie',
        'cookies',
        'storagestate',
        'setcookie',
        'set-cookie',
        'authorization',
        'password',
        'passwd',
        'secret',
        'token',
        'sessionstring',
        'rawsession',
        'localstorage',
        'indexeddb',
    ];

    public static function isForbiddenKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['_', '-'], '', $key));
        if (in_array(strtolower($key), self::FORBIDDEN_KEYS, true) || in_array($normalized, self::FORBIDDEN_KEYS, true)) {
            return true;
        }

        return (bool) preg_match('/cookie|storagestate|setcookie|password|passwd|rawsession|sessionstring/i', $key);
    }

    public static function assertNoSecrets(mixed $value, string $path = '$'): void
    {
        if (is_array($value)) {
            foreach ($value as $key => $nested) {
                $next = is_int($key) ? $path.'['.$key.']' : $path.'.'.$key;
                if (! is_int($key) && self::isForbiddenKey((string) $key)) {
                    throw new InvalidArgumentException('Refusing to send secret field '.$next);
                }
                self::assertNoSecrets($nested, $next);
            }
        }
    }

    public static function stripSecrets(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $out = [];
        foreach ($value as $key => $nested) {
            if (! is_int($key) && self::isForbiddenKey((string) $key)) {
                continue;
            }
            $out[$key] = self::stripSecrets($nested);
        }

        return $out;
    }
}
