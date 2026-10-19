<?php
require_once dirname(__DIR__) . '/php/helpers.php';

$studentsFile = dirname(__DIR__) . '/data/students.json';
$contactsFile = dirname(__DIR__) . '/data/contacts.json';

$students = read_json_file($studentsFile, []);
$contacts = read_json_file($contactsFile, []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - JSON Records Explorer (PHP)</title>
    <meta name="description" content="View live records stored in JSON format via PHP form processing.">
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/auth.js"></script>
    <script>
      // Blocking gate: Only authenticated admins may view complete student database records.
      if (window.AuthGate) window.AuthGate.requireAdmin();
    </script>
    <script defer src="../js/header.js"></script>
    <style>
        .record-card {
            background: var(--bg-card, #ffffff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 10px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .record-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .badge-id {
            background: var(--blue, #2563eb);
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
        }
        .json-preview {
            background: #0f172a;
            color: #38bdf8;
            font-family: Consolas, Monaco, monospace;
            font-size: 0.85rem;
            padding: 1rem;
            border-radius: 8px;
            overflow-x: auto;
            max-height: 400px;
        }
        .tab-btn {
            padding: 0.6rem 1.25rem;
            border: 1px solid var(--border-color, #e2e8f0);
            background: var(--bg-card, #ffffff);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            color: var(--text-color, #1e293b);
            transition: all 0.2s;
        }
        .tab-btn.active {
            background: var(--blue, #2563eb);
            color: #ffffff;
            border-color: var(--blue, #2563eb);
        }
    </style>
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
                <li><a href="view_records.php" class="nav-link active">JSON Records</a></li>
            </ul>
            <button type="button" class="theme-toggle">Dark Mode</button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-container" style="max-width: 1200px; margin: 2rem auto; padding: 0 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
            <div>
                <h1 style="font-size: 2rem; font-weight: 800; color: var(--navy, #1e293b); margin-bottom: 0.25rem;">Stored JSON Records</h1>
                <p style="color: var(--text-muted, #64748b); margin: 0;">Live database files processed and saved via PHP server-side validation & sanitization.</p>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <a href="register.php" class="btn btn-primary" style="padding: 0.6rem 1.25rem;">+ New Student Register</a>
                <a href="contact.php" class="btn btn-outline" style="padding: 0.6rem 1.25rem;">+ New Contact Query</a>
            </div>
        </div>

        <!-- Quick Summary Stats -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
            <div style="background: var(--bg-card, #ffffff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 10px; padding: 1.25rem;">
                <div style="font-size: 0.85rem; color: var(--text-muted, #64748b); font-weight: 600;">Registered Students</div>
                <div style="font-size: 2rem; font-weight: 800; color: var(--blue, #2563eb); margin-top: 0.25rem;"><?= count($students) ?></div>
                <div style="font-size: 0.75rem; color: var(--text-muted, #64748b); margin-top: 0.25rem;">File: <code>data/students.json</code></div>
            </div>
            <div style="background: var(--bg-card, #ffffff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 10px; padding: 1.25rem;">
                <div style="font-size: 0.85rem; color: var(--text-muted, #64748b); font-weight: 600;">Contact Messages</div>
                <div style="font-size: 2rem; font-weight: 800; color: #10b981; margin-top: 0.25rem;"><?= count($contacts) ?></div>
                <div style="font-size: 0.75rem; color: var(--text-muted, #64748b); margin-top: 0.25rem;">File: <code>data/contacts.json</code></div>
            </div>
            <div style="background: var(--bg-card, #ffffff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 10px; padding: 1.25rem;">
                <div style="font-size: 0.85rem; color: var(--text-muted, #64748b); font-weight: 600;">PHP Processor</div>
                <div style="font-size: 1.25rem; font-weight: 700; color: #6366f1; margin-top: 0.5rem;">Active & Validating</div>
                <div style="font-size: 0.75rem; color: var(--text-muted, #64748b); margin-top: 0.25rem;">Atomic <code>LOCK_EX</code> writes</div>
            </div>
        </div>

        <!-- Tab Controls -->
        <div style="display: flex; gap: 0.75rem; margin-bottom: 1.5rem; border-bottom: 1px solid var(--border-color, #e2e8f0); padding-bottom: 0.75rem;">
            <button type="button" class="tab-btn active" onclick="switchRecordTab('students')">🎓 Student Records (<?= count($students) ?>)</button>
            <button type="button" class="tab-btn" onclick="switchRecordTab('contacts')">📬 Contact Queries (<?= count($contacts) ?>)</button>
            <button type="button" class="tab-btn" onclick="switchRecordTab('rawjson')">📄 Raw JSON Data</button>
        </div>

        <!-- Tab 1: Students List -->
        <div id="tab-students">
            <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; color: var(--navy, #1e293b);">Registered Students (data/students.json)</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 1rem;">
                <?php if (empty($students)): ?>
                    <p style="color: var(--text-muted, #64748b);">No student records found in JSON file.</p>
                <?php else: ?>
                    <?php foreach ($students as $stu): ?>
                        <div class="record-card">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <span class="badge-id"><?= htmlspecialchars($stu['id'] ?? 'STU-000') ?></span>
                                <span style="font-size: 0.8rem; color: var(--text-muted, #64748b);"><?= htmlspecialchars($stu['registeredDate'] ?? date('Y-m-d')) ?></span>
                            </div>
                            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--navy, #1e293b); margin: 0 0 0.25rem;"><?= htmlspecialchars($stu['name'] ?? 'Unknown') ?></h3>
                            <div style="font-size: 0.85rem; color: var(--blue, #2563eb); font-weight: 600; margin-bottom: 0.5rem;">
                                <?= htmlspecialchars($stu['course'] ?? '') ?> &bull; <?= htmlspecialchars($stu['year'] ?? '') ?> (<?= htmlspecialchars($stu['department'] ?? '') ?>)
                            </div>
                            <div style="font-size: 0.85rem; color: var(--text-color, #475569); line-height: 1.5;">
                                <div>📧 <strong>Email:</strong> <?= htmlspecialchars($stu['email'] ?? '') ?></div>
                                <div>📱 <strong>Mobile:</strong> <?= htmlspecialchars($stu['mobile'] ?? '') ?></div>
                                <div>⚧ <strong>Gender:</strong> <?= htmlspecialchars($stu['gender'] ?? 'Not Specified') ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab 2: Contacts List -->
        <div id="tab-contacts" style="display: none;">
            <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; color: var(--navy, #1e293b);">Contact Submissions (data/contacts.json)</h2>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                <?php if (empty($contacts)): ?>
                    <p style="color: var(--text-muted, #64748b);">No contact submissions found in JSON file.</p>
                <?php else: ?>
                    <?php foreach ($contacts as $msg): ?>
                        <div class="record-card">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span class="badge-id" style="background: #10b981;"><?= htmlspecialchars($msg['id'] ?? 'MSG-000') ?></span>
                                    <span style="font-size: 0.75rem; background: #e0e7ff; color: #3730a3; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600;"><?= htmlspecialchars($msg['status'] ?? 'New') ?></span>
                                </div>
                                <span style="font-size: 0.8rem; color: var(--text-muted, #64748b);"><?= htmlspecialchars($msg['submittedAt'] ?? '') ?></span>
                            </div>
                            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--navy, #1e293b); margin: 0 0 0.25rem;"><?= htmlspecialchars($msg['subject'] ?? '') ?></h3>
                            <div style="font-size: 0.85rem; color: var(--text-muted, #64748b); margin-bottom: 0.5rem;">
                                From: <strong><?= htmlspecialchars($msg['name'] ?? '') ?></strong> (<?= htmlspecialchars($msg['email'] ?? '') ?>) &bull; IP: <code><?= htmlspecialchars($msg['ipAddress'] ?? '127.0.0.1') ?></code>
                            </div>
                            <div style="background: var(--bg-hover, #f8fafc); padding: 0.75rem; border-radius: 6px; font-size: 0.9rem; color: var(--text-color, #334155); border-left: 3px solid #10b981;">
                                <?= nl2br(htmlspecialchars($msg['message'] ?? '')) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab 3: Raw JSON Preview -->
        <div id="tab-rawjson" style="display: none;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem;">
                <div>
                    <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.5rem;">students.json</h3>
                    <pre class="json-preview"><code><?= htmlspecialchars(json_encode($students, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></code></pre>
                </div>
                <div>
                    <h3 style="font-size: 1rem; font-weight: 700; margin-bottom: 0.5rem;">contacts.json</h3>
                    <pre class="json-preview"><code><?= htmlspecialchars(json_encode($contacts, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></code></pre>
                </div>
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
        function switchRecordTab(tabName) {
            document.querySelectorAll('.tab-btn').forEach(function(b) { b.classList.remove('active'); });
            document.getElementById('tab-students').style.display = 'none';
            document.getElementById('tab-contacts').style.display = 'none';
            document.getElementById('tab-rawjson').style.display = 'none';

            if (tabName === 'students') {
                document.getElementById('tab-students').style.display = 'block';
                document.querySelectorAll('.tab-btn')[0].classList.add('active');
            } else if (tabName === 'contacts') {
                document.getElementById('tab-contacts').style.display = 'block';
                document.querySelectorAll('.tab-btn')[1].classList.add('active');
            } else if (tabName === 'rawjson') {
                document.getElementById('tab-rawjson').style.display = 'block';
                document.querySelectorAll('.tab-btn')[2].classList.add('active');
            }
        }

        // Support URL hash navigation (e.g. view_records.php#contacts-tab)
        if (window.location.hash.includes('contacts')) {
            switchRecordTab('contacts');
        }
    </script>
</body>
</html>
