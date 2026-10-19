<?php
/**
 * Student Hub - Protected Student Dashboard entry (Practical 10)
 * ----------------------------------------------------------------------------
 * Server-side guard: only authenticated users (any role) may view the
 * existing student dashboard. The page itself is unchanged (pages/
 * dashboard.html); a <base> tag is injected so relative asset/link URLs
 * keep resolving exactly as before. Client-side AuthGate still runs but is
 * no longer the authority - this check is.
 */
require_once __DIR__ . '/auth.php';

auth_start();
require_login();

$file = __DIR__ . '/../pages/dashboard.html';
if (!is_readable($file)) {
    http_response_code(404);
    exit('Dashboard page not found.');
}

$html = file_get_contents($file);
// Keep relative URLs working when served from /php/ instead of /pages/.
$html = preg_replace('/<head([^>]*)>/i', '<head$1><base href="../pages/">', $html, 1);
echo $html;
