<?php
/**
 * EduCore Application Configuration
 */
require_once __DIR__ . '/env.php';

define('APP_NAME', 'EduCore');
define('APP_VERSION', '1.0.0');

// If EduCore lives in a subfolder under your web root (e.g. XAMPP htdocs),
// set that subfolder path here, e.g. '/EduCore'. Leave blank if it's served
// from the web root — that's the default and matches php -S / a vhost docroot.
define('BASE_URL', env('APP_BASE_URL', ''));

/**
 * Build a root-relative URL that works no matter how deep the current page
 * is nested (admin/, faculty/, student/, ...), since it always resolves
 * from the site root rather than the current working directory.
 */
function url(string $path = ''): string
{
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}