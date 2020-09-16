<?php

// Enforce HTTPS requests.
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Enforce declared content-type on requests.
header('X-Content-Type-Options: nosniff');

// Block iframes.
header('X-Frame-Options: DENY');

// Set up CSP to allow all origins.
header('Content-Security-Policy: default-src * \'unsafe-inline\' \'unsafe-eval\'; script-src * \'unsafe-inline\' \'unsafe-eval\'; connect-src * \'unsafe-inline\'; img-src * data: blob: \'unsafe-inline\'; frame-src *; style-src * \'unsafe-inline\';');

// Enforce XSS protection to be enabled.
header('X-XSS-Protection: 1');

// Include Composer modules.
require_once 'vendor/autoload.php';

require_once 'includes/settings.php';

if (!isset($maintenance) || !$maintenance) {
    require_once 'includes/database.php';
}

require_once 'includes/functions.php';

if (session_status() != PHP_SESSION_ACTIVE) {
    session_start();
}

// Backend constants.
foreach ([SHARED_VALUES, ALLOWANCE_LEVEL] as $constantArray) {
    foreach ($constantArray as $const => $value) {
        define($const, $value);
    }
}

?>