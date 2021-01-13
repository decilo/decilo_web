<?php

use Spatie\Async\Pool;

class Main {
    private ?PDO  $database  = null;
    private ?Pool $asyncPool = null;

    private function loadDependencies() {
        // Include Composer modules.
        require_once 'vendor/autoload.php';
        
        require_once 'includes/settings.php';
        require_once 'includes/functions.php';
    }

    private function loadHeaders() {
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
    }

    private function connectDatabase() {
        if (!defined('MAINTENANCE') || !MAINTENANCE) {
            require_once 'includes/database.php';

            $this->database = new Database();
        }
    }

    private function refreshSession() {
        if (session_status() != PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    private function buildConstants() {
        // Backend constants.
        foreach ([SHARED_VALUES, ALLOWANCE_LEVEL] as $constantArray) {
            foreach ($constantArray as $const => $value) {
                define($const, $value);
            }
        }
    }

    private function initializeAsyncPool() {
        $this->asyncPool = Pool::create();
    }

    public function __construct() {
        $this->loadDependencies();
        $this->loadHeaders();
        $this->connectDatabase();
        $this->refreshSession();
        $this->buildConstants();
        $this->initializeAsyncPool();
    }

    public function getDatabase() {
        return $this->database;
    }

    public function getAsyncPool() {
        if ($this->asyncPool == null) {
            throw new Exception('Failed to create the main async pool.');
        }

        return $this->asyncPool;
    }
}

$main = new Main();

$database = $main->getDatabase();

?>