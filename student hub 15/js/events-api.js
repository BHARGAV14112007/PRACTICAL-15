/**
 * Student Hub - Live Events API client (Practical 13)
 * Fetches php/api_events.php and re-renders the table without any page reload.
 * Handles loading state, empty results, HTTP errors, invalid JSON and
 * network failures with clear messages.
 */
(function () {
    'use strict';

    var API = 'api_events.php';
    var state = { csrf: null, role: null, editingId: null };

    function $(id) { return document.getElementById(id); }

    function esc(value) {
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function setLoading(on) {
        var el = $('api-loading');
        if (el) el.style.display = on ? 'block' : 'none';
    }

    function showMessage(kind, text) {
        var el = $('api-message');
        if (!el) return;
        el.style.display = 'block';
        if (kind === 'success') {
            el.style.background = '#dcfce7'; el.style.border = '1px solid #86efac'; el.style.color = '#15803d';
        } else {
            el.style.background = '#fee2e2'; el.style.border = '1px solid #fca5a5'; el.style.color = '#b91c1c';
        }
        el.textContent = text;
    }

    function hideMessage() {
        var el = $('api-message');
        if (el) el.style.display = 'none';
    }

    // Read a response as JSON, tolerating empty / non-JSON bodies.
    async function readJson(res) {
        var text = await res.text();
        if (!text) return { success: false, message: 'Empty response from server.', data: [], errors: {} };
        try {
            return JSON.parse(text);
        } catch (e) {
            return { success: false, message: 'Server returned invalid JSON.', data: [], errors: {} };
        }
    }

    async function apiGet(action, params) {
        var url = API + '?action=' + encodeURIComponent(action);
        if (params) {
            Object.keys(params).forEach(function (k) {
                url += '&' + encodeURIComponent(k) + '=' + encodeURIComponent(params[k]);
            });
        }
        var res = await fetch(url, { headers: { 'Accept': 'application/json' } });
        var body = await readJson(res);
        return { status: res.status, body: body };
    }

    async function apiPost(payload) {
        var res = await fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        });
        var body = await readJson(res);
        return { status: res.status, body: body };
    }

    function renderRows(events) {
        var tbody = $('api-events-tbody');
        if (!tbody) return;
        if (!events || events.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--text-muted);">No events found.</td></tr>';
            return;
        }
        var admin = state.role === 'admin';
        tbody.innerHTML = events.map(function (ev) {
            var actions = admin
                ? '<td style="white-space:nowrap;">' +
                  '<button type="button" class="btn btn-sm btn-outline" data-edit="' + esc(ev.event_id) + '">Edit</button> ' +
                  '<button type="button" class="btn btn-sm btn-danger" data-delete="' + esc(ev.event_id) + '">Delete</button></td>'
                : '';
            return '<tr>' +
                '<td>' + esc(ev.event_id) + '</td>' +
                '<td><strong>' + esc(ev.title) + '</strong></td>' +
                '<td>' + esc(ev.category) + '</td>' +
                '<td>' + esc(ev.event_date) + '</td>' +
                '<td>' + esc(ev.venue) + '</td>' +
                '<td>' + esc(ev.status) + '</td>' + actions + '</tr>';
        }).join('');

        tbody.querySelectorAll('[data-edit]').forEach(function (btn) {
            btn.addEventListener('click', function () { startEdit(btn.getAttribute('data-edit')); });
        });
        tbody.querySelectorAll('[data-delete]').forEach(function (btn) {
            btn.addEventListener('click', function () { removeEvent(btn.getAttribute('data-delete')); });
        });
    }

    async function loadEvents() {
        setLoading(true);
        hideMessage();
        try {
            var out = await apiGet('list');
            if (out.status === 200 && out.body.success) {
                renderRows(out.body.data.events || []);
            } else {
                renderRows([]);
                showMessage('error', out.body.message || ('Could not load events (HTTP ' + out.status + ').'));
            }
        } catch (err) {
            renderRows([]);
            showMessage('error', 'Network error: could not reach the server. Check XAMPP/Apache and try again.');
        } finally {
            setLoading(false);
        }
    }

    async function initSession() {
        // Who am I? A 401 simply means "visitor": list stays public, the
        // management form stays hidden. Server-side checks are authoritative.
        try {
            var out = await apiGet('csrf');
            if (out.status === 200 && out.body.success) {
                state.csrf = out.body.data.csrf_token;
                state.role = out.body.data.role;
                var badge = $('api-role-badge');
                if (badge) {
                    badge.textContent = 'Signed in as ' + out.body.data.name + ' (' + state.role + ')';
                    badge.style.display = 'inline-block';
                }
                if (state.role === 'admin') {
                    var mgmt = $('api-manage');
                    if (mgmt) mgmt.style.display = 'block';
                }
                return;
            }
        } catch (err) { /* visitor mode on network failure */ }
        var note = $('api-login-note');
        if (note) note.style.display = 'block';
    }

    function fillForm(ev) {
        $('ef-id').value = ev.event_id;
        $('ef-title').value = ev.title || '';
        $('ef-description').value = ev.description || '';
        $('ef-category').value = ev.category || 'Other';
        $('ef-date').value = ev.event_date || '';
        $('ef-venue').value = ev.venue || '';
        $('ef-organizer').value = ev.organizer || '';
        $('ef-seats').value = ev.seats_available !== null ? ev.seats_available : 0;
        $('ef-status').value = ev.status || 'Upcoming';
        $('ef-submit').textContent = 'Update Event (async)';
        state.editingId = ev.event_id;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function resetForm() {
        var form = $('api-event-form');
        if (form) form.reset();
        $('ef-id').value = '';
        $('ef-submit').textContent = 'Add Event (async)';
        state.editingId = null;
    }

    async function startEdit(id) {
        hideMessage();
        try {
            var out = await apiGet('get', { id: id });
            if (out.status === 200 && out.body.success) {
                fillForm(out.body.data.event);
            } else {
                showMessage('error', out.body.message || 'Could not load that event.');
            }
        } catch (err) {
            showMessage('error', 'Network error while loading the event.');
        }
    }

    async function removeEvent(id) {
        if (!window.confirm('Delete event #' + id + ' permanently?')) return;
        hideMessage();
        try {
            var out = await apiPost({ action: 'delete', id: id, csrf_token: state.csrf });
            if (out.status === 200 && out.body.success) {
                showMessage('success', out.body.message);
                await loadEvents();
            } else {
                showMessage('error', out.body.message || ('Delete failed (HTTP ' + out.status + ').'));
            }
        } catch (err) {
            showMessage('error', 'Network error: delete not completed.');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        loadEvents();
        initSession();

        var form = $('api-event-form');
        if (form) {
            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                hideMessage();
                var isEdit = !!state.editingId;
                var payload = {
                    action: isEdit ? 'update' : 'add',
                    csrf_token: state.csrf,
                    title: $('ef-title').value,
                    description: $('ef-description').value,
                    category: $('ef-category').value,
                    event_date: $('ef-date').value,
                    venue: $('ef-venue').value,
                    organizer: $('ef-organizer').value,
                    seats_available: $('ef-seats').value,
                    status: $('ef-status').value
                };
                if (isEdit) payload.id = state.editingId;
                try {
                    var out = await apiPost(payload);
                    if ((out.status === 200 || out.status === 201) && out.body.success) {
                        showMessage('success', out.body.message);
                        resetForm();
                        await loadEvents();
                    } else {
                        var errs = out.body.errors || {};
                        var first = Object.keys(errs).map(function (k) { return errs[k]; }).join(' ');
                        showMessage('error', (out.body.message || 'Save failed.') + (first ? ' ' + first : ''));
                    }
                } catch (err) {
                    showMessage('error', 'Network error: changes not saved.');
                }
            });

            var cancel = $('ef-cancel');
            if (cancel) cancel.addEventListener('click', resetForm);
        }
    });
})();
