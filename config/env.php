<?php
/**
 * Environment configuration loader
 * Loads .env file and provides clean getenv() wrapper
 */

function loadEnv(): void
{
    $envFile = __DIR__ . '/../.env';
    if (!file_exists($envFile)) {
        error_log('[EduCore] No .env file found; using defaults. Copy .env.example to .env to customize.');
        return;
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    if (in_array(strtolower($value), ['true', 'yes', '1', 'on'], true)) {
        return true;
    }
    if (in_array(strtolower($value), ['false', 'no', '0', 'off'], true)) {
        return false;
    }
    return $value;
}

loadEnv();
