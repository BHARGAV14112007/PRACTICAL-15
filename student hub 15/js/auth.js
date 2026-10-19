/**
 * Page-Level Authentication & Access Control Gate (Student Hub)
 *
 * Provides blocking redirect guards for:
 *   • Admin-only pages (admin.html)        → must have student_hub_is_admin === 'true'
 *   • Authenticated-user pages (dashboard, profile, events) → must be logged in (student or admin)
 *
 * This file is meant to be loaded via <script> tag in the <head> (or early
 * in the <body>) of the protected page so the redirect happens BEFORE any
 * visible content flashes.
 *
 * Stores and reads:
 *   localStorage
 *     ├─ student_hub_logged_in  === 'true'
 *     ├─ student_hub_is_admin   === 'true'
 *     └─ student_hub_current_user  { name, email, ... } JSON
 */

(function () {
  'use strict';

  // ---------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------
  function isUserLoggedIn() {
    try {
      return (
        localStorage.getItem('student_hub_logged_in') === 'true' ||
        localStorage.getItem('student_hub_current_user') !== null
      );
    } catch (e) {
      return false;
    }
  }

  function isUserAdmin() {
    try {
      return localStorage.getItem('student_hub_is_admin') === 'true';
    } catch (e) {
      return false;
    }
  }

  // Figure out relative path to login.html (we live in /pages/*.html so sibling)
  function redirectToLogin(roleHint) {
    var target = 'login.html';
    if (roleHint === 'admin') {
      target += '?role=admin';
    }
    try {
      window.location.replace(target);
    } catch (e) {
      window.location.href = target;
    }
  }

  // ---------------------------------------------------------------
  // Public Gate API
  // ---------------------------------------------------------------
  window.AuthGate = {
    /**
     * Block the current page unless the current visitor is a logged-in
     * administrator. If the check fails, redirect to admin login.
     */
    requireAdmin: function () {
      if (!isUserLoggedIn()) {
        redirectToLogin('admin');
        return false;
      }
      if (!isUserAdmin()) {
        // Logged in but not an admin → kick to admin login
        redirectToLogin('admin');
        return false;
      }
      return true;
    },

    /**
     * Block the current page unless the current visitor is authenticated
     * (either a student OR an admin). Otherwise redirect to student login.
     */
    requireLogin: function () {
      if (!isUserLoggedIn()) {
        redirectToLogin('student');
        return false;
      }
      return true;
    },

    isLoggedIn: isUserLoggedIn,
    isAdmin: isUserAdmin
  };
})();
