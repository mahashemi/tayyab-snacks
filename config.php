<?php
// Tayyab Snacks — Database & Site Configuration
// Update these values for your hosting environment

define('DB_HOST', 'localhost');
define('DB_USER', 'root');          // Change to your DB username
define('DB_PASS', '');              // Change to your DB password
define('DB_NAME', 'tayyab_snacks');

define('SITE_NAME', 'Tayyab Snacks');
define('SITE_TAGLINE', 'Pure Snacks. Pure Intentions. Pure Community.');
define('SITE_URL', '');             // e.g. https://tayyabsnacks.com

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
