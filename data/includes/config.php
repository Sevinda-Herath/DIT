<?php
declare(strict_types=1);

// Configure your MySQL connection here or via environment variables.
// Ensure the database exists and the user has privileges to CREATE/ALTER tables.

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'nebula');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
