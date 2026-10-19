import { fetchJson } from "./api.js";
import { renderCards, renderPagination, showLoading, showError, updateResultCount } from "./ui.js";

const PAGE_SIZE = 5;
const searchInput = document.getElementById("search-input");
const filterSelect = document.getElementById("filter-select");
const sortSelect = document.getElementById("sort-select");
const pagination = document.getElementById("pagination");
const sectionTitle = document.getElementById("section-title");
const tabButtons = document.querySelectorAll(".tab-button");

const configurations = [
  {
    type: "events", title: "College Events", searchPlaceholder: "Search events...", filterField: "category", searchFields: ["title", "description", "venue", "organizer"],
    sortOptions: [["date-asc", "Date: earliest first"], ["date-desc", "Date: latest first"], ["title-asc", "Title: A to Z"], ["title-desc", "Title: Z to A"]]
  },
  {
    type: "students", title: "College Students", searchPlaceholder: "Search students...", filterField: "department", searchFields: ["name", "email", "city", "department"],
    sortOptions: [["name-asc", "Name: A to Z"], ["name-desc", "Name: Z to A"]]
  },
  {
    type: "faqs", title: "Frequently Asked Questions", searchPlaceholder: "Search FAQs...", filterField: "category", searchFields: ["question", "answer", "category"],
    sortOptions: [["question-asc", "Question: A to Z"], ["question-desc", "Question: Z to A"]]
  }
];

let currentType = "events";
let currentData = [];
let currentPage = 1;

function getConfiguration() {
  return configurations.find((item) => item.type === currentType);
}

function fillControls() {
  const config = getConfiguration();
  const categories = [...new Set(currentData.map((item) => item[config.filterField]).filter(Boolean))].sort();

  searchInput.value = "";
  searchInput.placeholder = config.searchPlaceholder;
  filterSelect.innerHTML = `<option value="all">All ${config.filterField === "department" ? "departments" : "categories"}</option>` +
    categories.map((category) => `<option value="${category}">${category}</option>`).join("");
  sortSelect.innerHTML = config.sortOptions.map(([value, text]) => `<option value="${value}">${text}</option>`).join("");
  sectionTitle.textContent = config.title;
}

function getProcessedData() {
  const config = getConfiguration();
  const searchText = searchInput.value.trim().toLowerCase();
  const filterValue = filterSelect.value;

  const filteredData = currentData.filter((item) => {
    const matchesSearch = config.searchFields.some((field) => 
      item[field] && item[field].toString().toLowerCase().includes(searchText)
    );
    const matchesFilter = filterValue === "all" || item[config.filterField] === filterValue;
    return matchesSearch && matchesFilter;
  });

  const sortedData = [...filteredData].sort((first, second) => {
    const [field, order] = sortSelect.value.split("-");
    const firstValue = first[field] || "";
    const secondValue = second[field] || "";
    const comparison = currentType === "events" && field === "date"
      ? new Date(firstValue) - new Date(secondValue)
      : firstValue.toString().localeCompare(secondValue.toString());
    return order === "asc" ? comparison : -comparison;
  });

  return sortedData;
}

function updateView() {
  const processedData = getProcessedData();
  const totalPages = Math.max(1, Math.ceil(processedData.length / PAGE_SIZE));
  currentPage = Math.min(currentPage, totalPages);
  const startIndex = (currentPage - 1) * PAGE_SIZE;
  const pageData = processedData.slice(startIndex, startIndex + PAGE_SIZE);

  updateResultCount(processedData.length);
  renderCards(pageData, currentType);
  renderPagination(currentPage, totalPages);
}

async function loadData(type) {
  currentType = type;
  currentPage = 1;
  showError("");
  showLoading(true);

  try {
    let rawData = await fetchJson(type);

    // If viewing students, merge with any newly registered students saved in localStorage
    if (type === "students") {
      try {
        const localStudents = JSON.parse(localStorage.getItem("student_hub_students") || "[]");
        const existingEmails = new Set(rawData.map((s) => s.email));
        localStudents.forEach((ls) => {
          if (!existingEmails.has(ls.email)) {
            rawData.unshift(ls);
          }
        });
      } catch (e) {
        console.warn("Could not read local students:", e);
      }
    }

    currentData = rawData;
    fillControls();
    updateView();
  } catch (error) {
    currentData = [];
    updateResultCount(0);
    renderCards([], type);
    renderPagination(1, 0);
    showError("Unable to load data. Please try again.");
  } finally {
    showLoading(false);
  }
}

tabButtons.forEach((button) => {
  button.addEventListener("click", () => {
    tabButtons.forEach((tab) => tab.classList.remove("active"));
    button.classList.add("active");
    loadData(button.dataset.type);
  });
});

[searchInput, filterSelect, sortSelect].forEach((control) => {
  control.addEventListener(control === searchInput ? "input" : "change", () => {
    currentPage = 1;
    updateView();
  });
});

pagination.addEventListener("click", (event) => {
  const button = event.target.closest("button[data-page]");
  if (!button || button.disabled) return;

  const totalPages = Math.ceil(getProcessedData().length / PAGE_SIZE);
  if (button.dataset.page === "previous") currentPage--;
  else if (button.dataset.page === "next") currentPage++;
  else currentPage = Number(button.dataset.page);
  currentPage = Math.max(1, Math.min(currentPage, totalPages));
  updateView();
});

loadData(currentType);
