<?php
/**
 * config/database.php
 * Central PDO connection factory for EduCore.
 * Every other file (dbcon.php, controllers, api endpoints) should get its
 * connection through getDbConnection() rather than opening PDO directly,
 * so credentials and options live in exactly one place.
 */

declare(strict_types=1);

function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // TODO: move these into environment variables / a non-committed .env
    // before deploying anywhere beyond localhost.
    $host    = 'localhost';
    $dbName  = 'educore';
    $charset = 'utf8mb4';
    $user    = 'root';
    $pass    = '';

    $dsn = "mysql:host={$host};dbname={$dbName};charset={$charset}";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // Never leak DSN/credentials or raw PDO exceptions to the browser.
        error_log('[EduCore] DB connection failed: ' . $e->getMessage());
        http_response_code(500);
        die('EduCore is temporarily unavailable. Please try again shortly.');
    }

    return $pdo;
}