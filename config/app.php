<?php
/**
 * EduCore Application Configuration
 */
define('APP_NAME', 'EduCore');
define('APP_VERSION', '1.0.0');

// Adjust this to match your XAMPP/local path
define('BASE_URL', '/Ardent Internship 2026/Ardent PHP Internship 2026 Final Project');

function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}
