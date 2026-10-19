/**
 * Events Page Dynamic Controller
 * Fetches data from data/events.json using Fetch API
 * Implements Live Search, Category Filtering, Sorting, and 4 items-per-page Pagination
 */

const PAGE_SIZE = 4; // Exactly 4 events per page as requested

let allEvents = [];
let filteredEvents = [];
let currentPage = 1;
let currentCategory = 'all';
let currentSort = 'date-asc';
let searchQuery = '';

// DOM Elements
const eventsContainer = document.getElementById('events-container');
const paginationContainer = document.getElementById('events-pagination');
const searchInput = document.getElementById('event-search-input');
const categorySelect = document.getElementById('event-category-select');
const sortSelect = document.getElementById('event-sort-select');
const resultCountEl = document.getElementById('event-result-count');
const loadingEl = document.getElementById('events-loading');
const categoryTabs = document.querySelectorAll('.category-tab');

// Fetch events from localStorage or JSON
async function fetchEvents() {
    if (loadingEl) loadingEl.style.display = 'block';
    if (eventsContainer) eventsContainer.innerHTML = '';

    try {
        const storedEvents = localStorage.getItem('student_hub_events');
        if (storedEvents) {
            allEvents = JSON.parse(storedEvents);
        } else {
            const response = await fetch('../data/events.json');
            if (!response.ok) {
                throw new Error(`HTTP Error: ${response.status}`);
            }
            allEvents = await response.json();
            localStorage.setItem('student_hub_events', JSON.stringify(allEvents));
        }
        
        // Populate category dropdown
        populateCategoryOptions();
        
        // Initial render
        applyFiltersAndRender();
    } catch (error) {
        console.error('Failed to load events:', error);
        if (eventsContainer) {
            eventsContainer.innerHTML = `
                <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 2rem; color: #b42318;">
                    <h3>Unable to load events</h3>
                    <p style="margin-top: 0.5rem; color: var(--muted);">Please ensure the JSON data file exists and try refreshing.</p>
                </div>`;
        }
    } finally {
        if (loadingEl) loadingEl.style.display = 'none';
    }
}

function populateCategoryOptions() {
    if (!categorySelect) return;
    const categories = Array.from(new Set(allEvents.map(e => e.category))).sort();
    categorySelect.innerHTML = '<option value="all">All Categories</option>' +
        categories.map(c => `<option value="${c}">${c}</option>`).join('');
}

function getRegisteredEventIds() {
    try {
        return JSON.parse(localStorage.getItem('student_hub_registered_events') || '[]');
    } catch (e) {
        return [];
    }
}

function toggleEventRegistration(eventId, buttonElement) {
    let registered = getRegisteredEventIds();
    const isRegistered = registered.includes(eventId);

    if (isRegistered) {
        registered = registered.filter(id => id !== eventId);
        buttonElement.textContent = 'Register Now';
        buttonElement.className = 'btn btn-primary btn-block';
    } else {
        registered.push(eventId);
        buttonElement.textContent = '✓ Registered';
        buttonElement.className = 'btn btn-outline btn-block';
        buttonElement.style.borderColor = '#16a34a';
        buttonElement.style.color = '#16a34a';
    }

    localStorage.setItem('student_hub_registered_events', JSON.stringify(registered));
}

function getCategoryBadgeColor(category) {
    switch (category) {
        case 'Technical': return '#2563d9';
        case 'Cultural': return '#d97706';
        case 'Sports': return '#16a34a';
        case 'Workshop': return '#7c3aed';
        case 'Seminar': return '#0891b2';
        default: return '#102a43';
    }
}

function renderEventCard(event) {
    const registeredIds = getRegisteredEventIds();
    const isRegistered = registeredIds.includes(event.id);
    const badgeColor = getCategoryBadgeColor(event.category);

    return `
        <article class="card event-card" style="text-align: left; padding: 0; overflow: hidden; display: flex; flex-direction: column; height: 100%; border-radius: 10px; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); transition: transform 0.2s, box-shadow 0.2s;">
            <div style="background: linear-gradient(135deg, #163B65 0%, ${badgeColor} 100%); padding: 1.25rem 1.25rem 1rem; color: white; position: relative;">
                <span style="display: inline-block; background: rgba(255,255,255,0.22); color: #ffffff; padding: 0.25rem 0.65rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                    ${event.category}
                </span>
                <h3 style="font-size: 1.15rem; font-weight: 700; color: #ffffff; margin-top: 0.6rem; line-height: 1.3;">
                    ${event.title}
                </h3>
            </div>
            
            <div style="padding: 1.25rem; display: flex; flex-direction: column; flex: 1; justify-content: space-between;">
                <div>
                    <div style="font-size: 0.875rem; color: var(--text-muted); margin-bottom: 0.85rem; line-height: 1.6;">
                        <p>📅 <strong>Date:</strong> ${event.date}</p>
                        ${event.time ? `<p>⏰ <strong>Time:</strong> ${event.time}</p>` : ''}
                        <p>📍 <strong>Venue:</strong> ${event.venue}</p>
                        <p>👤 <strong>Organizer:</strong> ${event.organizer}</p>
                        ${event.seatsAvailable ? `<p>🎟️ <strong>Capacity:</strong> ${event.seatsAvailable} seats</p>` : ''}
                    </div>
                    
                    <p style="color: var(--text-main); font-size: 0.9rem; line-height: 1.5; margin-bottom: 1.25rem;">
                        ${event.description}
                    </p>
                </div>

                <div>
                    <button type="button" 
                        class="${isRegistered ? 'btn btn-outline btn-block' : 'btn btn-primary btn-block'}"
                        style="${isRegistered ? 'border-color: #16a34a; color: #16a34a; font-weight: 600;' : ''}"
                        onclick="window.handleRegisterClick(${event.id}, this)">
                        ${isRegistered ? '✓ Registered' : 'Register Now'}
                    </button>
                </div>
            </div>
        </article>
    `;
}

function applyFiltersAndRender() {
    const q = searchQuery.toLowerCase().trim();

    // 1. Search & Category Filter
    filteredEvents = allEvents.filter(event => {
        const matchesCategory = currentCategory === 'all' || event.category.toLowerCase() === currentCategory.toLowerCase();
        const matchesSearch = !q || 
            event.title.toLowerCase().includes(q) ||
            event.category.toLowerCase().includes(q) ||
            event.organizer.toLowerCase().includes(q) ||
            event.venue.toLowerCase().includes(q) ||
            (event.description && event.description.toLowerCase().includes(q));

        return matchesCategory && matchesSearch;
    });

    // 2. Sorting
    filteredEvents.sort((a, b) => {
        if (currentSort === 'date-asc') {
            return new Date(a.date) - new Date(b.date);
        } else if (currentSort === 'date-desc') {
            return new Date(b.date) - new Date(a.date);
        } else if (currentSort === 'title-asc') {
            return a.title.localeCompare(b.title);
        } else if (currentSort === 'title-desc') {
            return b.title.localeCompare(a.title);
        }
        return 0;
    });

    // 3. Pagination calculation (4 items per page)
    const totalPages = Math.max(1, Math.ceil(filteredEvents.length / PAGE_SIZE));
    if (currentPage > totalPages) {
        currentPage = 1;
    }

    const startIndex = (currentPage - 1) * PAGE_SIZE;
    const pageItems = filteredEvents.slice(startIndex, startIndex + PAGE_SIZE);

    // 4. Update count label
    if (resultCountEl) {
        resultCountEl.textContent = `Showing ${filteredEvents.length === 0 ? 0 : startIndex + 1}-${Math.min(startIndex + PAGE_SIZE, filteredEvents.length)} of ${filteredEvents.length} events`;
    }

    // 5. Render cards
    if (eventsContainer) {
        if (pageItems.length === 0) {
            eventsContainer.innerHTML = `
                <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 3rem; background: var(--bg-card);">
                    <p style="font-size: 1.1rem; color: var(--text-muted); margin-bottom: 1rem;">No campus events match your current filter criteria.</p>
                    <button type="button" class="btn btn-outline" onclick="window.resetEventFilters()">Reset Filters</button>
                </div>`;
        } else {
            eventsContainer.innerHTML = pageItems.map(renderEventCard).join('');
        }
    }

    // 6. Render pagination controls
    renderPagination(totalPages);
}

function renderPagination(totalPages) {
    if (!paginationContainer) return;

    if (totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }

    let buttonsHtml = `
        <button class="page-button" type="button" ${currentPage === 1 ? 'disabled' : ''} onclick="window.goToEventPage(${currentPage - 1})">
            &laquo; Previous
        </button>
    `;

    for (let p = 1; p <= totalPages; p++) {
        buttonsHtml += `
            <button class="page-button ${p === currentPage ? 'active' : ''}" type="button" onclick="window.goToEventPage(${p})">
                ${p}
            </button>
        `;
    }

    buttonsHtml += `
        <button class="page-button" type="button" ${currentPage === totalPages ? 'disabled' : ''} onclick="window.goToEventPage(${currentPage + 1})">
            Next &raquo;
        </button>
    `;

    paginationContainer.innerHTML = buttonsHtml;
}

// Global window helpers for inline HTML event handlers
window.goToEventPage = function (page) {
    currentPage = page;
    applyFiltersAndRender();
    const target = document.getElementById('events-section-anchor') || eventsContainer;
    if (target) {
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
};

window.handleRegisterClick = function (eventId, btn) {
    toggleEventRegistration(eventId, btn);
};

window.resetEventFilters = function () {
    searchQuery = '';
    currentCategory = 'all';
    currentSort = 'date-asc';
    currentPage = 1;
    if (searchInput) searchInput.value = '';
    if (categorySelect) categorySelect.value = 'all';
    if (sortSelect) sortSelect.value = 'date-asc';
    categoryTabs.forEach(tab => {
        if (tab.getAttribute('data-category') === 'all') {
            tab.classList.add('btn-primary');
            tab.classList.remove('btn-outline');
        } else {
            tab.classList.remove('btn-primary');
            tab.classList.add('btn-outline');
        }
    });
    applyFiltersAndRender();
};

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    // Search input listener
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value;
            currentPage = 1;
            applyFiltersAndRender();
        });
    }

    // Category dropdown listener
    if (categorySelect) {
        categorySelect.addEventListener('change', (e) => {
            currentCategory = e.target.value;
            currentPage = 1;
            
            // Sync category tabs if available
            categoryTabs.forEach(tab => {
                const tabCat = tab.getAttribute('data-category');
                if (tabCat === currentCategory) {
                    tab.classList.add('btn-primary');
                    tab.classList.remove('btn-outline');
                } else {
                    tab.classList.remove('btn-primary');
                    tab.classList.add('btn-outline');
                }
            });

            applyFiltersAndRender();
        });
    }

    // Sort select listener
    if (sortSelect) {
        sortSelect.addEventListener('change', (e) => {
            currentSort = e.target.value;
            applyFiltersAndRender();
        });
    }

    // Category Tabs click listener
    categoryTabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            categoryTabs.forEach(t => {
                t.classList.remove('btn-primary');
                t.classList.add('btn-outline');
            });
            tab.classList.add('btn-primary');
            tab.classList.remove('btn-outline');

            currentCategory = tab.getAttribute('data-category') || 'all';
            if (categorySelect) categorySelect.value = currentCategory;
            currentPage = 1;
            applyFiltersAndRender();
        });
    });

    // Start fetching
    fetchEvents();
});
