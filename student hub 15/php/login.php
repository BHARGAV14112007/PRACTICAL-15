<?php
/**
 * Student Hub - Server-Side Login Form (Practical 10)
 * Presentation only: authentication lives in php/process_login.php and
 * session handling in php/auth.php.
 */
require_once __DIR__ . '/auth.php';

auth_start();

$errors = $_SESSION['login_errors'] ?? [];
$old = $_SESSION['login_old'] ?? [];
unset($_SESSION['login_errors'], $_SESSION['login_old']);

// One-time status banners from redirects (logout, guards, timeout).
$notice = null;
if (isset($_GET['logged_out'])) {
    $notice = ['type' => 'success', 'text' => 'You have been logged out securely. See you soon!'];
} elseif (isset($_GET['error'])) {
    if ($_GET['error'] === 'timeout') {
        $notice = ['type' => 'error', 'text' => 'Your session expired after 15 minutes of inactivity. Please log in again.'];
    } elseif ($_GET['error'] === 'forbidden') {
        $notice = ['type' => 'error', 'text' => 'Access denied: your account is not authorized for that page.'];
    } elseif ($_GET['error'] === 'auth') {
        $notice = ['type' => 'error', 'text' => 'Please log in to access that page.'];
    }
}

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Login (Practical 10)</title>
    <meta name="description" content="Sign in to Student Hub with server-side session authentication.">
    <link rel="stylesheet" href="../css/style.css">
    <script defer src="../js/header.js"></script>
</head>
<body>

    <!-- Header Navigation Bar -->
    <header class="navbar" role="navigation" aria-label="Main navigation">
        <div class="nav-container">
            <div class="nav-actions">
                <a href="login.php" class="btn btn-outline">Login</a>
                <a href="register.php" class="btn btn-primary">Register</a>
            </div>
            <a href="../pages/home.html" class="nav-brand">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 10v6M2 10l10-5 10 5-10 5z"/>
                    <path d="M6 12v5c3 3 9 3 12 0v-5"/>
                </svg>
                <span>STUDENT HUB</span>
            </a>
            <ul class="nav-links">
                <li><a href="../pages/home.html" class="nav-link">Home</a></li>
                <li><a href="../pages/faq.html" class="nav-link">FAQ</a></li>
                <li><a href="../pages/about.html" class="nav-link">About</a></li>
                <li><a href="contact.php" class="nav-link">Contact</a></li>
            </ul>
            <button type="button" class="theme-toggle">Dark Mode</button>
        </div>
    </header>

    <!-- Main Centered Content -->
    <main class="main-container">
        <div class="auth-center-wrapper">
            <div class="auth-card" style="width: 100%; max-width: 460px; margin: 0 auto;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                    <h1 class="auth-title" style="margin-bottom: 0;">Welcome Back</h1>
                    <span style="background: var(--blue-subtle, #e0e7ff); color: var(--blue, #2563eb); font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 9999px;">Sessions & Roles</span>
                </div>
                <p class="auth-subtitle">Sign in with your registered email and password</p>

                <?php if ($notice): ?>
                    <div role="<?= $notice['type'] === 'success' ? 'status' : 'alert' ?>" style="padding: 0.85rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.875rem; <?= $notice['type'] === 'success' ? 'background: #dcfce7; border: 1px solid #86efac; color: #15803d;' : 'background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c;' ?>">
                        <?= h($notice['text']) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert" style="background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                        <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.875rem;">
                            <?php foreach ($errors as $errMsg): ?>
                                <li><?= h($errMsg) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="process_login.php" novalidate>
                    <div class="form-group">
                        <label class="form-label" for="login-email">Email Address</label>
                        <input type="email" id="login-email" name="email" class="form-control <?= isset($errors['email']) || isset($errors['auth']) ? 'input-error' : '' ?>" placeholder="alex@university.edu" required autocomplete="email" value="<?= h($old['email'] ?? '') ?>">
                        <p class="field-error"><?= h($errors['email'] ?? '') ?></p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="login-password">Password</label>
                        <input type="password" id="login-password" name="password" class="form-control <?= isset($errors['password']) || isset($errors['auth']) ? 'input-error' : '' ?>" placeholder="Enter your password" required autocomplete="current-password">
                        <p class="field-error"><?= h($errors['password'] ?? '') ?></p>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="padding: 0.75rem; font-weight: 600;">Login to Dashboard</button>
                </form>

                <p class="auth-footer-text">
                    Don't have an account? <a href="register_mysql.php" style="color: var(--primary-color); font-weight: 600;">Register Here</a>
                </p>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <p class="footer-copy">&copy; 2026 Student Hub. All rights reserved.</p>
            <div class="social-links">
                <a href="#" class="social-link">Facebook</a>
                <a href="#" class="social-link">Twitter</a>
                <a href="#" class="social-link">Instagram</a>
                <a href="#" class="social-link">LinkedIn</a>
            </div>
        </div>
    </footer>

</body>
</html>
