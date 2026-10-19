/**
 * FAQs Page Dynamic Controller
 * Fetches data from data/faqs.json using Fetch API
 * Implements Search, Category Filtering, and Dynamic Accordion Rendering
 */

let allFaqs = [];
let filteredFaqs = [];
let currentCategory = 'all';
let searchQuery = '';

const faqsContainer = document.getElementById('faqs-container');
const searchInput = document.getElementById('faq-search-input');
const resultCountEl = document.getElementById('faq-result-count');
const categoryTabs = document.querySelectorAll('.faq-category-tab');
const loadingEl = document.getElementById('faq-loading');

async function fetchFaqs() {
    if (loadingEl) loadingEl.style.display = 'block';

    try {
        const response = await fetch('../data/faqs.json');
        if (!response.ok) {
            throw new Error(`HTTP error: ${response.status}`);
        }
        allFaqs = await response.json();
        applyFaqFilters();
    } catch (error) {
        console.error('Failed to load FAQs:', error);
        if (faqsContainer) {
            faqsContainer.innerHTML = `
                <div class="card" style="text-align: center; padding: 2rem; color: #b42318;">
                    <h3>Unable to load FAQs</h3>
                    <p style="margin-top: 0.5rem; color: var(--muted);">Please ensure the data/faqs.json file exists and try again.</p>
                </div>`;
        }
    } finally {
        if (loadingEl) loadingEl.style.display = 'none';
    }
}

function renderFaqItem(faq, index) {
    // Open the first item by default
    const isOpen = index === 0 ? 'open' : '';
    return `
        <details class="faq-accordion" ${isOpen} style="margin-bottom: 0.85rem; border: 1px solid var(--border-color); border-radius: 8px; background: var(--bg-card); overflow: hidden; transition: all 0.2s;">
            <summary class="faq-question" style="padding: 1rem 1.25rem; font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center; color: var(--text-main); user-select: none;">
                <span>${faq.question}</span>
                <span style="font-size: 0.75rem; font-weight: 600; text-transform: uppercase; background: var(--primary-light); color: var(--primary-color); padding: 0.2rem 0.6rem; border-radius: 12px; margin-left: 0.75rem; flex-shrink: 0;">
                    ${faq.category}
                </span>
            </summary>
            <div class="faq-answer" style="padding: 0.75rem 1.25rem 1.25rem; color: var(--text-muted); line-height: 1.6; border-top: 1px solid var(--border-color); background: #fafbfc;">
                ${faq.answer}
            </div>
        </details>
    `;
}

function applyFaqFilters() {
    const q = searchQuery.toLowerCase().trim();

    filteredFaqs = allFaqs.filter(faq => {
        const matchesCategory = currentCategory === 'all' || faq.category.toLowerCase() === currentCategory.toLowerCase();
        const matchesSearch = !q || 
            faq.question.toLowerCase().includes(q) || 
            faq.answer.toLowerCase().includes(q) ||
            faq.category.toLowerCase().includes(q);

        return matchesCategory && matchesSearch;
    });

    if (resultCountEl) {
        resultCountEl.textContent = `Showing ${filteredFaqs.length} of ${allFaqs.length} questions`;
    }

    if (faqsContainer) {
        if (filteredFaqs.length === 0) {
            faqsContainer.innerHTML = `
                <div class="card text-center" style="padding: 2.5rem; background: var(--bg-card);">
                    <p style="color: var(--text-muted); font-size: 1.05rem; margin-bottom: 1rem;">No FAQs matched your search term "<strong>${searchQuery}</strong>".</p>
                    <button type="button" class="btn btn-outline" onclick="window.resetFaqSearch()">View All FAQs</button>
                </div>`;
        } else {
            faqsContainer.innerHTML = filteredFaqs.map((faq, idx) => renderFaqItem(faq, idx)).join('');
        }
    }
}

window.resetFaqSearch = function () {
    searchQuery = '';
    currentCategory = 'all';
    if (searchInput) searchInput.value = '';
    categoryTabs.forEach(tab => {
        if (tab.getAttribute('data-category') === 'all') {
            tab.classList.add('btn-primary');
            tab.classList.remove('btn-outline');
        } else {
            tab.classList.remove('btn-primary');
            tab.classList.add('btn-outline');
        }
    });
    applyFaqFilters();
};

document.addEventListener('DOMContentLoaded', () => {
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value;
            applyFaqFilters();
        });
    }

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
            applyFaqFilters();
        });
    });

    fetchFaqs();
});
