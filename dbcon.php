<?php
/**
 * Legacy database connection — redirects to PDO config
 * @deprecated Use config/database.php instead
 */
require_once __DIR__ . '/config/database.php';

function getLegacyConnection(): PDO
{
    return getDB();
}
