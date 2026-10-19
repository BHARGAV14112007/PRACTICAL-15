// Header controls: mobile navigation, SVG theme toggle icons, and user authentication state sync.
document.addEventListener('DOMContentLoaded', function () {
  const navLinks = document.querySelector('.nav-links');
  const navContainer = document.querySelector('.nav-container');

  // Mobile Hamburger Toggle
  if (navContainer && navLinks && !document.querySelector('.nav-toggle')) {
    const menuButton = document.createElement('button');
    menuButton.className = 'nav-toggle';
    menuButton.type = 'button';
    menuButton.setAttribute('aria-label', 'Open menu');
    menuButton.textContent = '\u2630';
    menuButton.addEventListener('click', function () {
      const isOpen = navLinks.classList.toggle('open');
      menuButton.setAttribute('aria-label', isOpen ? 'Close menu' : 'Open menu');
    });
    navContainer.prepend(menuButton);
  }

  // SVG Theme Toggle (Sun / Moon icons instead of text)
  const themeButton = document.querySelector('.theme-toggle');
  const moonIconSvg = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>`;
  const sunIconSvg = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="5"></circle><line x1="12" y1="1" x2="12" y2="3"></line><line x1="12" y1="21" x2="12" y2="23"></line><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line><line x1="1" y1="12" x2="3" y2="12"></line><line x1="21" y1="12" x2="23" y2="12"></line><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line></svg>`;

  // Restore saved theme preference
  const isDarkModeSaved = localStorage.getItem('student_hub_theme') === 'dark';
  if (isDarkModeSaved) {
    document.documentElement.classList.add('dark-mode');
  }

  function updateThemeButtonIcon() {
    if (!themeButton) return;
    const isDark = document.documentElement.classList.contains('dark-mode');
    themeButton.innerHTML = isDark ? sunIconSvg : moonIconSvg;
    themeButton.setAttribute('title', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
    themeButton.setAttribute('aria-label', isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode');
  }

  if (themeButton) {
    updateThemeButtonIcon();
    themeButton.addEventListener('click', function () {
      const isDark = document.documentElement.classList.toggle('dark-mode');
      localStorage.setItem('student_hub_theme', isDark ? 'dark' : 'light');
      updateThemeButtonIcon();
    });
  }

  // User Authentication State Sync (Hide Login/Register when user is logged in)
  syncUserAuthState();
  attachSidebarLogoutHandlers();
});

function syncUserAuthState() {
  const navActions = document.querySelector('.nav-actions');
  const currentUserJson = localStorage.getItem('student_hub_current_user');
  const isLoggedIn = localStorage.getItem('student_hub_logged_in') === 'true' || currentUserJson !== null;
  const isAdmin = localStorage.getItem('student_hub_is_admin') === 'true';

  let user = null;
  if (currentUserJson) {
    try {
      user = JSON.parse(currentUserJson);
    } catch (e) {
      console.warn('Could not parse user JSON:', e);
    }
  }

  // Sync user display names in sidebar / headers
  if (user) {
    const nameEls = document.querySelectorAll('.user-display-name');
    nameEls.forEach(el => { el.textContent = user.name || 'Student'; });

    const roleEls = document.querySelectorAll('.user-display-role');
    roleEls.forEach(el => {
      if (isAdmin) {
        el.textContent = user.role || 'Administrator';
      } else if (user.course && user.year) {
        el.textContent = `${user.course} · ${user.year}`;
      } else if (user.role) {
        el.textContent = user.role;
      }
    });
  }

  // Hide Login and Register buttons when logged in; show profile + logout
  if (navActions) {
    if (isLoggedIn && user) {
      const initials = (user.name || 'U').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
      const profileLink = isAdmin ? 'admin.html' : 'profile.html';
      const roleBadge = isAdmin ? '<span style="font-size: 0.7rem; background: #dc2626; color: white; padding: 0.1rem 0.4rem; border-radius: 10px; font-weight: 700; margin-left: 0.35rem;">ADMIN</span>' : '';

      navActions.innerHTML = `
        <div style="display: flex; align-items: center; gap: 0.75rem;">
          <a href="${profileLink}" style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none; color: var(--text-main); font-weight: 600; font-size: 0.9rem;" title="View Profile">
            <div style="width: 32px; height: 32px; border-radius: 50%; background: var(--primary-color); color: white; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700;">
              ${initials}
            </div>
            <span>${user.name ? user.name.split(' ')[0] : 'User'}</span>
            ${roleBadge}
          </a>
          <button type="button" class="btn btn-sm btn-outline" onclick="window.handleGlobalLogout()" style="padding: 0.35rem 0.75rem; font-size: 0.85rem;">
            Logout
          </button>
        </div>
      `;
    }
  }
}

function attachSidebarLogoutHandlers() {
  // Match any anchor whose text (trimmed, lowercased) equals "logout" regardless of href
  const allAnchors = document.querySelectorAll('a');
  allAnchors.forEach(link => {
    const linkText = link.textContent.trim().toLowerCase();
    if (linkText === 'logout' || linkText === '🚪 logout' || linkText === 'sign out') {
      // Remove any previous click listeners (best effort via clone)
      const newLink = link.cloneNode(true);
      newLink.addEventListener('click', function (e) {
        e.preventDefault();
        window.handleGlobalLogout();
      });
      if (link.parentNode) {
        link.parentNode.replaceChild(newLink, link);
      }
    }
  });
}

// Global Logout Handler – works from both root index.html level and pages/ sub-folder
window.handleGlobalLogout = function () {
  localStorage.removeItem('student_hub_logged_in');
  localStorage.removeItem('student_hub_is_admin');
  localStorage.removeItem('student_hub_current_user');
  // Figure out whether we are at the root or inside pages/ folder
  const currentPath = window.location.pathname;
  const isInsidePagesFolder = /\/pages\//.test(currentPath) || currentPath.includes('/pages/') ||
    (window.location.href.includes('/pages/'));
  window.location.href = isInsidePagesFolder ? 'home.html' : 'pages/home.html';
};
