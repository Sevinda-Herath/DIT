<?php
declare(strict_types=1);

// Configure your MySQL connection here or via environment variables.
// Ensure the database exists and the user has privileges to CREATE/ALTER tables.

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'nebula');
// Require DB credentials from environment (no hardcoded defaults)
$__db_user = getenv('DB_USER');
if ($__db_user === false || $__db_user === '') {
	if (php_sapi_name() !== 'cli') { http_response_code(500); }
	exit('Configuration error: DB_USER environment variable is required');
}
define('DB_USER', $__db_user);

$__db_pass = getenv('DB_PASS');
if ($__db_pass === false || $__db_pass === '') {
	if (php_sapi_name() !== 'cli') { http_response_code(500); }
	exit('Configuration error: DB_PASS environment variable is required');
}
define('DB_PASS', $__db_pass);
