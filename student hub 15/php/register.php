<?php
require_once dirname(__DIR__) . '/php/helpers.php';

$errors = $_SESSION['form_errors'] ?? [];
$old = $_SESSION['form_old'] ?? [];
$successMessage = $_SESSION['flash_success'] ?? null;

// Clear flash errors after consuming them
unset($_SESSION['form_errors']);
unset($_SESSION['flash_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Register (PHP Backend)</title>
    <meta name="description" content="Register an account with Student Hub. Server-side validated and stored in JSON format with PHP.">
    <link rel="stylesheet" href="../css/style.css">
    <script defer src="../js/header.js"></script>
    <script defer src="../js/script.js"></script>
</head>
<body>

    <!-- Header Navigation Bar -->
    <header class="navbar" role="navigation" aria-label="Main navigation">
        <div class="nav-container">
            <div class="nav-actions">
                <a href="../pages/login.html" class="btn btn-outline">Login</a>
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
                <li><a href="view_records.php" class="nav-link">JSON Records</a></li>
            </ul>
            <button type="button" class="theme-toggle">Dark Mode</button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-container">
        <div class="auth-center-wrapper">
            <!-- Register Form Card -->
            <div class="auth-card" style="width: 100%; max-width: 520px; margin: 0 auto;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                    <h1 class="auth-title" style="margin-bottom: 0;">Create Account</h1>
                    <span style="background: var(--blue-subtle, #e0e7ff); color: var(--blue, #2563eb); font-size: 0.75rem; font-weight: 700; padding: 0.25rem 0.6rem; border-radius: 9999px;">PHP & JSON Backend</span>
                </div>
                <p class="auth-subtitle">Server-side validated & sanitized records saved directly to JSON format</p>

                <!-- Server-Side Success Message Banner -->
                <?php if ($successMessage): ?>
                    <div class="alert alert-success" role="alert" style="background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                        <div style="font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            Registration Successful!
                        </div>
                        <p style="margin: 0.5rem 0 0; font-size: 0.9rem;"><?= htmlspecialchars($successMessage) ?></p>
                        <div style="margin-top: 0.75rem; display: flex; gap: 0.5rem;">
                            <a href="view_records.php" class="btn btn-outline" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">View JSON Records</a>
                            <a href="../pages/profile.html" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">Go to Profile</a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Server-Side Error Alert Summary -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" role="alert" style="background-color: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                        <div style="font-weight: 700; margin-bottom: 0.25rem;">Please correct the following errors:</div>
                        <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.875rem;">
                            <?php foreach ($errors as $field => $errMsg): ?>
                                <li><?= htmlspecialchars($errMsg) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Dynamic Form (Processes to PHP) -->
                <form id="registration-form" action="../php/process_register.php" method="POST" novalidate>
                    <div class="form-group">
                        <label class="form-label" for="reg-name">Full Name</label>
                        <input type="text" id="reg-name" name="fullname" class="form-control <?= isset($errors['fullname']) ? 'input-error' : '' ?>" placeholder="Alex Johnson" required minlength="2" autocomplete="name" value="<?= htmlspecialchars($old['fullname'] ?? '') ?>" aria-describedby="name-error">
                        <p class="field-error" id="name-error" aria-live="polite"><?= htmlspecialchars($errors['fullname'] ?? '') ?></p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reg-email">Email Address</label>
                        <input type="email" id="reg-email" name="email" class="form-control <?= isset($errors['email']) ? 'input-error' : '' ?>" placeholder="alex@university.edu" required autocomplete="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" aria-describedby="email-error">
                        <p class="field-error" id="email-error" aria-live="polite"><?= htmlspecialchars($errors['email'] ?? '') ?></p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reg-mobile">Mobile Number</label>
                        <input type="tel" id="reg-mobile" name="mobile" class="form-control <?= isset($errors['mobile']) ? 'input-error' : '' ?>" placeholder="9876543210" required inputmode="numeric" autocomplete="tel" pattern="[6-9][0-9]{9}" maxlength="10" value="<?= htmlspecialchars($old['mobile'] ?? '') ?>" aria-describedby="mobile-error">
                        <p class="field-error" id="mobile-error" aria-live="polite"><?= htmlspecialchars($errors['mobile'] ?? '') ?></p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reg-course">Course</label>
                        <select id="reg-course" name="course" class="form-control <?= isset($errors['course']) ? 'input-error' : '' ?>" required aria-describedby="course-error">
                            <option value="">Select your course</option>
                            <option value="BCA" <?= (($old['course'] ?? '') === 'BCA') ? 'selected' : '' ?>>BCA</option>
                            <option value="BBA" <?= (($old['course'] ?? '') === 'BBA') ? 'selected' : '' ?>>BBA</option>
                            <option value="B.Tech" <?= (($old['course'] ?? '') === 'B.Tech') ? 'selected' : '' ?>>B.Tech</option>
                            <option value="Other" <?= (($old['course'] ?? '') === 'Other') ? 'selected' : '' ?>>Other</option>
                        </select>
                        <p class="field-error" id="course-error" aria-live="polite"><?= htmlspecialchars($errors['course'] ?? '') ?></p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reg-year">Academic Year</label>
                        <select id="reg-year" name="year" class="form-control <?= isset($errors['year']) ? 'input-error' : '' ?>" required aria-describedby="year-error">
                            <option value="">Select your year</option>
                            <option value="First Year" <?= (($old['year'] ?? '') === 'First Year') ? 'selected' : '' ?>>First Year</option>
                            <option value="Second Year" <?= (($old['year'] ?? '') === 'Second Year') ? 'selected' : '' ?>>Second Year</option>
                            <option value="Third Year" <?= (($old['year'] ?? '') === 'Third Year') ? 'selected' : '' ?>>Third Year</option>
                            <option value="Final Year" <?= (($old['year'] ?? '') === 'Final Year') ? 'selected' : '' ?>>Final Year</option>
                        </select>
                        <p class="field-error" id="year-error" aria-live="polite"><?= htmlspecialchars($errors['year'] ?? '') ?></p>
                    </div>

                    <fieldset class="form-group gender-group" aria-describedby="gender-error">
                        <legend class="form-label">Gender</legend>
                        <div class="radio-options">
                            <label><input type="radio" name="gender" value="Male" <?= (($old['gender'] ?? '') === 'Male') ? 'checked' : '' ?> required> Male</label>
                            <label><input type="radio" name="gender" value="Female" <?= (($old['gender'] ?? '') === 'Female') ? 'checked' : '' ?>> Female</label>
                            <label><input type="radio" name="gender" value="Prefer not to say" <?= (($old['gender'] ?? '') === 'Prefer not to say') ? 'checked' : '' ?>> Prefer not to say</label>
                        </div>
                        <p class="field-error" id="gender-error" aria-live="polite"><?= htmlspecialchars($errors['gender'] ?? '') ?></p>
                    </fieldset>

                    <div class="form-group">
                        <label class="form-label" for="reg-password">Password</label>
                        <input type="password" id="reg-password" name="password" class="form-control <?= isset($errors['password']) ? 'input-error' : '' ?>" placeholder="Create password (min 8 chars)" required minlength="8" autocomplete="new-password" aria-describedby="password-strength password-error">
                        <p class="password-strength" id="password-strength" aria-live="polite">Password strength: Not entered</p>
                        <p class="field-error" id="password-error" aria-live="polite"><?= htmlspecialchars($errors['password'] ?? '') ?></p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="reg-confirm">Confirm Password</label>
                        <input type="password" id="reg-confirm" name="confirm_password" class="form-control <?= isset($errors['confirm_password']) ? 'input-error' : '' ?>" placeholder="Confirm password" required autocomplete="new-password" aria-describedby="confirm-error">
                        <p class="field-error" id="confirm-error" aria-live="polite"><?= htmlspecialchars($errors['confirm_password'] ?? '') ?></p>
                    </div>

                    <div class="form-group terms-group">
                        <label class="form-checkbox" for="reg-terms">
                            <input type="checkbox" id="reg-terms" name="terms" value="1" required aria-describedby="terms-error">
                            I accept the campus terms and conditions.
                        </label>
                        <p class="field-error" id="terms-error" aria-live="polite"><?= htmlspecialchars($errors['terms'] ?? '') ?></p>
                    </div>

                    <button type="submit" id="reg-submit-btn" class="btn btn-primary btn-block" style="padding: 0.75rem; margin-top: 1rem; font-weight: 600;">
                        <span>Register with PHP & JSON</span>
                    </button>
                    <p class="form-success" id="form-success" role="status" aria-live="polite" hidden></p>
                </form>

                <p class="auth-footer-text">
                    Already have an account? <a href="../pages/login.html" style="color: var(--blue); font-weight: 600;">Log In Here</a>
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
