<?php
/**
 * Student Hub - Protected Admin entry (Practical 10)
 * ----------------------------------------------------------------------------
 * Server-side guard: only authenticated users with role 'admin' may view the
 * existing admin page (pages/admin.html). Students or guests are redirected
 * to the login page. See php/dashboard.php for the <base> technique.
 */
require_once __DIR__ . '/auth.php';

auth_start();
require_role('admin');

$file = __DIR__ . '/../pages/admin.html';
if (!is_readable($file)) {
    http_response_code(404);
    exit('Admin page not found.');
}

$html = file_get_contents($file);
// Keep relative URLs working when served from /php/ instead of /pages/.
$html = preg_replace('/<head([^>]*)>/i', '<head$1><base href="../pages/">', $html, 1);
echo $html;
