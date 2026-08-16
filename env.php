<?php
/**
 * Environment configuration loader
 * Loads .env file and provides clean getenv() wrapper
 */

function loadEnv(): void
{
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) {
        // No .env present (e.g. fresh clone before local setup). Fall back to
        // XAMPP/local defaults rather than fatally killing every page — see
        // .env.example for the keys you can override.
        error_log('[EduCore] No .env file found; using default local settings. Copy .env.example to .env to customize.');
        return;
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

/**
 * Get environment variable with optional default
 */
function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    
    // Parse boolean strings
    if (in_array(strtolower($value), ['true', 'yes', '1', 'on'], true)) {
        return true;
    }
    if (in_array(strtolower($value), ['false', 'no', '0', 'off'], true)) {
        return false;
    }
    
    return $value;
}

// Load on first include
loadEnv();