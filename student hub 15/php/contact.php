<?php
require_once dirname(__DIR__) . '/php/helpers.php';

$errors = $_SESSION['contact_errors'] ?? [];
$old = $_SESSION['contact_old'] ?? [];
$successMessage = $_SESSION['contact_success'] ?? null;

// Clear flash errors after consuming them
unset($_SESSION['contact_errors']);
unset($_SESSION['contact_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Contact Us (PHP Backend)</title>
    <meta name="description" content="Have questions or feedback? Contact the Student Hub support team. Server-side validated and stored in JSON format with PHP.">
    <link rel="stylesheet" href="../css/style.css">
    <script defer src="../js/header.js"></script>
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
                <li><a href="../pages/about.html" class="nav-link">About</a></li>
                <li><a href="../pages/faq.html" class="nav-link">FAQ</a></li>
                <li><a href="contact.php" class="nav-link active">Contact</a></li>
                <li><a href="view_records.php" class="nav-link">JSON Records</a></li>
            </ul>
            <button type="button" class="theme-toggle">Dark Mode</button>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="main-container">
        <div class="page-header text-center" style="margin-top: 1rem;">
            <div style="display: inline-block; background: var(--blue-subtle, #e0e7ff); color: var(--blue, #2563eb); font-size: 0.8rem; font-weight: 700; padding: 0.3rem 0.8rem; border-radius: 9999px; margin-bottom: 0.5rem;">
                PHP Server-Side Processing & JSON Storage
            </div>
            <h1 class="page-title" style="font-size: 2.25rem;">Get in Touch</h1>
            <p class="page-subtitle">Have a question or suggestion? Submissions are validated with PHP and stored in JSON format.</p>
        </div>

        <div class="hero-wrapper" style="padding: 1.5rem 0; gap: 2rem;">
            <!-- Contact Form Card -->
            <div class="auth-card" style="width: 100%; max-width: 560px; margin: 0 auto; box-shadow: var(--shadow-sm);">
                
                <!-- Server-Side Success Message Banner -->
                <?php if ($successMessage): ?>
                    <div class="alert alert-success" role="alert" style="background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                        <div style="font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            Message Sent Successfully!
                        </div>
                        <p style="margin: 0.5rem 0 0; font-size: 0.9rem;"><?= htmlspecialchars($successMessage) ?></p>
                        <div style="margin-top: 0.75rem;">
                            <a href="view_records.php#contacts-tab" class="btn btn-outline" style="font-size: 0.8rem; padding: 0.35rem 0.75rem;">View Stored Messages (JSON)</a>
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

                <!-- Client-Side Dynamic Alert Area -->
                <div id="contact-ajax-alert" hidden style="margin-bottom: 1.5rem; padding: 1rem; border-radius: 8px;"></div>

                <form id="contact-form" action="../php/process_contact.php" method="POST" novalidate>
                    <div class="form-group">
                        <label class="form-label" for="contact-name">Your Full Name</label>
                        <input type="text" id="contact-name" name="name" class="form-control <?= isset($errors['name']) ? 'input-error' : '' ?>" placeholder="Alex Johnson" required minlength="2" value="<?= htmlspecialchars($old['name'] ?? '') ?>" aria-describedby="contact-name-error">
                        <p class="field-error" id="contact-name-error" aria-live="polite"><?= htmlspecialchars($errors['name'] ?? '') ?></p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contact-email">Email Address</label>
                        <input type="email" id="contact-email" name="email" class="form-control <?= isset($errors['email']) ? 'input-error' : '' ?>" placeholder="alex@university.edu" required value="<?= htmlspecialchars($old['email'] ?? '') ?>" aria-describedby="contact-email-error">
                        <p class="field-error" id="contact-email-error" aria-live="polite"><?= htmlspecialchars($errors['email'] ?? '') ?></p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contact-subject">Subject</label>
                        <input type="text" id="contact-subject" name="subject" class="form-control <?= isset($errors['subject']) ? 'input-error' : '' ?>" placeholder="Event Inquiry / Technical Support / Campus Portal" required minlength="3" value="<?= htmlspecialchars($old['subject'] ?? '') ?>" aria-describedby="contact-subject-error">
                        <p class="field-error" id="contact-subject-error" aria-live="polite"><?= htmlspecialchars($errors['subject'] ?? '') ?></p>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contact-message">Message Content</label>
                        <textarea id="contact-message" name="message" class="form-control <?= isset($errors['message']) ? 'input-error' : '' ?>" rows="4" placeholder="Type your message here (minimum 10 characters)..." required minlength="10" aria-describedby="contact-message-error"><?= htmlspecialchars($old['message'] ?? '') ?></textarea>
                        <p class="field-error" id="contact-message-error" aria-live="polite"><?= htmlspecialchars($errors['message'] ?? '') ?></p>
                    </div>

                    <button type="submit" id="contact-submit-btn" class="btn btn-primary btn-block" style="padding: 0.75rem; font-weight: 600;">
                        <span>Send Message (PHP Process)</span>
                    </button>
                </form>
            </div>

            <!-- Contact Info & Illustration -->
            <div>
                <div class="card mb-3" style="text-align: left; background: var(--bg-card, #ffffff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 12px; padding: 1.5rem;">
                    <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--navy, #1e293b); margin-bottom: 1rem;">Contact Information</h3>
                    <p style="margin-bottom: 0.75rem;">📧 <strong>Email:</strong> support@studenthub.edu</p>
                    <p style="margin-bottom: 0.75rem;">📞 <strong>Phone:</strong> +91 98765 43210</p>
                    <p style="margin-bottom: 0.75rem;">📍 <strong>Address:</strong> Student Activity Center, Building 4, University Campus</p>
                    <p style="margin-bottom: 0.75rem;">🕒 <strong>Hours:</strong> Mon - Fri: 8:00 AM - 6:00 PM</p>
                    <p style="margin-bottom: 0;">📁 <strong>Storage File:</strong> <code>data/contacts.json</code></p>
                </div>

                <svg class="hero-illustration" viewBox="0 0 400 240" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect width="400" height="240" rx="12" fill="#EAF3FF"/>
                    <rect x="50" y="35" width="300" height="160" rx="8" fill="#2563D9"/>
                    <path d="M50 35L200 135L350 35" stroke="#DBEAFE" stroke-width="6"/>
                </svg>
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

    <script>
        // Progressive Enhancement: Handle contact form with AJAX if JS enabled
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('contact-form');
            var alertBox = document.getElementById('contact-ajax-alert');
            var submitBtn = document.getElementById('contact-submit-btn');

            if (!form) return;

            form.addEventListener('submit', function (e) {
                // If running on a live web server with PHP, submit asynchronously
                if (window.location.protocol.startsWith('http')) {
                    e.preventDefault();
                    
                    var formData = new FormData(form);
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Processing with PHP...';
                    
                    // Clear previous field errors
                    document.querySelectorAll('.field-error').forEach(function (el) { el.textContent = ''; });
                    document.querySelectorAll('.form-control').forEach(function (el) { el.classList.remove('input-error'); });

                    fetch('../php/process_contact.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Send Message (PHP Process)';

                        if (data.success) {
                            alertBox.hidden = false;
                            alertBox.style.display = 'block';
                            alertBox.style.backgroundColor = '#dcfce7';
                            alertBox.style.color = '#15803d';
                            alertBox.style.border = '1px solid #86efac';
                            alertBox.innerHTML = `<strong>Success!</strong> ${data.message} <br><a href="view_records.php#contacts-tab" style="font-weight:600; text-decoration:underline; color:#15803d;">View JSON Records &rarr;</a>`;
                            form.reset();
                        } else {
                            alertBox.hidden = false;
                            alertBox.style.display = 'block';
                            alertBox.style.backgroundColor = '#fee2e2';
                            alertBox.style.color = '#b91c1c';
                            alertBox.style.border = '1px solid #fca5a5';
                            alertBox.textContent = data.message || 'Validation failed. Please check inputs.';

                            if (data.errors) {
                                for (var key in data.errors) {
                                    var errEl = document.getElementById('contact-' + key + '-error');
                                    var inputEl = document.getElementById('contact-' + key);
                                    if (errEl) errEl.textContent = data.errors[key];
                                    if (inputEl) inputEl.classList.add('input-error');
                                }
                            }
                        }
                    })
                    .catch(function (err) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Send Message (PHP Process)';
                        // Normal submission fallback
                        form.submit();
                    });
                }
            });
        });
    </script>
</body>
</html>
