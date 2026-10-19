<?php
/**
 * StudentHub - Role-based navigation (Practical 14)
 * ----------------------------------------------------------------------------
 * render_nav($active) prints the <ul class="nav-links"> menu for the current
 * session role. Guests see public links only, students never see admin-only
 * options, admins see everything. This only controls VISIBILITY: every
 * restricted page still enforces require_login()/require_role() server-side.
 */

function render_nav($active = '') {
    $role = isset($_SESSION['user']['role']) ? $_SESSION['user']['role'] : 'guest';

    $links = [
        ['../pages/home.html', 'Home', 'home', ['guest', 'student', 'admin']],
        ['../pages/faq.html', 'FAQ', 'faq', ['guest', 'student', 'admin']],
        ['../pages/about.html', 'About', 'about', ['guest', 'student', 'admin']],
        ['contact.php', 'Contact', 'contact', ['guest', 'student', 'admin']],
        ['dashboard.php', 'Dashboard', 'dashboard', ['student', 'admin']],
        ['students.php', 'Students', 'students', ['student', 'admin']],
        ['events_live.php', 'Live Events', 'live', ['student', 'admin']],
        ['admin_dashboard.php', 'Admin Dashboard', 'admin', ['admin']],
        ['logout.php', 'Logout', 'logout', ['student', 'admin']],
    ];

    if ($role === 'guest') {
        $links[] = ['login.php', 'Login', 'login', ['guest']];
    }

    echo '<ul class="nav-links">';
    foreach ($links as $link) {
        list($href, $label, $key, $roles) = $link;
        if (!in_array($role, $roles, true)) {
            continue;
        }
        $cls = 'nav-link' . ($key === $active ? ' active' : '');
        echo '<li><a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8')
            . '" class="' . $cls . '">'
            . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a></li>';
    }
    echo '</ul>';
}
