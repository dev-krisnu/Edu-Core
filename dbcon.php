<?php
/**
 * dbcon.php
 * Root-level convenience include so legacy-style `require 'dbcon.php'`
 * calls anywhere in the project still resolve to the single PDO factory
 * in config/database.php. New code should require config/database.php
 * directly; this file just keeps older references working.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

$pdo = getDbConnection();