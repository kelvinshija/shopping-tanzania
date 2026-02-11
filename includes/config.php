<?php
// Configuration file
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ecommerce_db');

// IMPORTANT: Change this to match your actual folder path
define('BASE_URL', 'http://localhost/new shopping/');
define('SITE_NAME', 'SHOPPING TZ');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>