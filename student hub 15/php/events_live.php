<?php
/**
 * Student Hub - Live Events (Practical 13 demo page)
 * Everything on this page updates via Fetch (js/events-api.js) with no page
 * reloads. Reading is public; the management form appears only for signed-in
 * admins, and the API re-checks auth + CSRF server-side on every write.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Hub - Live Events (Practical 13)</title>
    <meta name="description" content="Practical 13: events loaded and managed asynchronously via JSON API + Fetch.">
    <link rel="stylesheet" href="../css/style.css">
    <script defer src="../js/header.js"></script>
    <script defer src="../js/events-api.js"></script>
</head>
<body>

    <header class="navbar" role="navigation" aria-label="Main navigation">
        <div class="nav-container">
            <div class="nav-actions">
                <a href="../pages/login.html" class="btn btn-outline">Login</a>
                <a href="../pages/register.html" class="btn btn-primary">Register</a>
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
                <li><a href="events_live.php" class="nav-link active">Live Events</a></li>
            </ul>
            <button type="button" class="theme-toggle">Dark Mode</button>
        </div>
    </header>

    <main class="main-container" style="max-width: 1100px; margin: 2rem auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
            <div>
                <h1 class="page-title">Live Events <span style="font-size: 0.8rem; background: #e0e7ff; color: #3730a3; padding: 0.25rem 0.6rem; border-radius: 9999px; font-weight: 700;">Practical 13 · Fetch API</span></h1>
                <p class="page-subtitle">Loaded from <code>php/api_events.php</code> with no page reloads. <span id="api-role-badge" style="display: none; background: #dcfce7; color: #15803d; padding: 0.2rem 0.6rem; border-radius: 9999px; font-weight: 700;"></span></p>
            </div>
            <button type="button" class="btn btn-outline" onclick="window.location.reload()">Refresh Page</button>
        </div>

        <div id="api-message" role="status" style="display: none; padding: 0.85rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.9rem;"></div>

        <div id="api-login-note" role="note" style="display: none; background: #e0f2fe; border: 1px solid #7dd3fc; color: #0369a1; padding: 0.85rem 1rem; border-radius: 8px; margin-bottom: 1.25rem; font-size: 0.9rem;">
            You are browsing as a visitor. <a href="login.php" style="font-weight: 700;">Log in as admin</a> to add, edit, or delete events asynchronously.
        </div>

        <!-- Async management form (admins only, revealed by JS) -->
        <div class="card" id="api-manage" style="display: none;">
            <h3 style="margin-top: 0;">Add / Edit Event (async)</h3>
            <form id="api-event-form">
                <input type="hidden" id="ef-id" value="">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                    <div class="form-group">
                        <label class="form-label" for="ef-title">Title</label>
                        <input type="text" id="ef-title" class="form-control" required maxlength="150">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ef-category">Category</label>
                        <select id="ef-category" class="form-control">
                            <option>Workshop</option><option>Technical</option><option>Cultural</option>
                            <option>Sports</option><option>Seminar</option><option>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ef-date">Date</label>
                        <input type="date" id="ef-date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ef-venue">Venue</label>
                        <input type="text" id="ef-venue" class="form-control" required maxlength="120">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ef-organizer">Organizer</label>
                        <input type="text" id="ef-organizer" class="form-control" maxlength="120">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ef-seats">Seats</label>
                        <input type="number" id="ef-seats" class="form-control" min="0" max="100000" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ef-status">Status</label>
                        <select id="ef-status" class="form-control">
                            <option>Upcoming</option><option>Ongoing</option><option>Completed</option><option>Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="ef-description">Description</label>
                        <textarea id="ef-description" class="form-control" rows="2" maxlength="2000"></textarea>
                    </div>
                </div>
                <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                    <button type="submit" id="ef-submit" class="btn btn-primary">Add Event (async)</button>
                    <button type="button" id="ef-cancel" class="btn btn-outline">Cancel Edit</button>
                </div>
            </form>
        </div>

        <!-- Async table -->
        <div class="card">
            <div id="api-loading" style="text-align: center; color: var(--text-muted); padding: 1rem;">Loading events…</div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>ID</th><th>Title</th><th>Category</th><th>Date</th><th>Venue</th><th>Status</th></tr>
                    </thead>
                    <tbody id="api-events-tbody">
                        <tr><td colspan="6" style="text-align:center;color:var(--text-muted);">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

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
