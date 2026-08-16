<?php
/**
 * EduCore Application Configuration
 */
require_once __DIR__ . '/env.php';

define('APP_NAME', 'EduCore');
define('APP_VERSION', '1.0.0');

/**
 * Resolve the web path where EduCore is installed.
 * Example: /Ardent Internship 2026/Ardent PHP Internship 2026 Final Project
 * Set APP_BASE_URL in .env only if auto-detect fails.
 */
function detectBaseUrl(): string
{
    $fromEnv = env('APP_BASE_URL');
    if (is_string($fromEnv) && $fromEnv !== '') {
        return '/' . trim(str_replace('\\', '/', $fromEnv), '/');
    }

    $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
    $appRoot = realpath(dirname(__DIR__)) ?: '';

    if ($docRoot !== '' && $appRoot !== '' && str_starts_with($appRoot, $docRoot)) {
        $relative = str_replace('\\', '/', substr($appRoot, strlen($docRoot)));
        return $relative === '' ? '' : $relative;
    }

    return '';
}

define('BASE_URL', detectBaseUrl());

/**
 * Build a root-relative URL (path segments are encoded for spaces etc.).
 */
function url(string $path = ''): string
{
    $segments = [];

    $base = trim(BASE_URL, '/');
    if ($base !== '') {
        $segments = explode('/', $base);
    }

    $path = trim($path, '/');
    if ($path !== '') {
        $segments = array_merge($segments, explode('/', $path));
    }

    if ($segments === []) {
        return '/';
    }

    return '/' . implode('/', array_map('rawurlencode', $segments));
}

/**
 * Web path from a role subfolder back to project root (for assets in includes).
 * admin/foo.php → ".."
 */
function webRootFromScript(): string
{
    $dir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
    $dir = trim(str_replace('\\', '/', $dir), '/');
    if ($dir === '') {
        return '.';
    }
    return implode('/', array_fill(0, substr_count($dir, '/') + 1, '..'));
}
