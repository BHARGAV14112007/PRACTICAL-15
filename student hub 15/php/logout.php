<?php
/**
 * Student Hub - Secure Logout (Practical 10)
 * Clears and destroys the session, then returns to the login page.
 */
require_once __DIR__ . '/auth.php';

auth_start();
auth_logout();

header('Location: login.php?logged_out=1');
exit;
