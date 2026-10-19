const resultsContainer = document.getElementById("results-container");
const paginationContainer = document.getElementById("pagination");

function createEventCard(event) {
  return `
    <article class="card">
      <span class="tag">${event.category}</span>
      <h3>${event.title}</h3>
      <p><strong>Date:</strong> ${event.date}</p>
      <p><strong>Venue:</strong> ${event.venue}</p>
      <p><strong>Organizer:</strong> ${event.organizer}</p>
      <p>${event.description}</p>
    </article>`;
}

function createStudentCard(student) {
  return `
    <article class="card">
      <span class="tag">${student.department}</span>
      <h3>${student.name}</h3>
      <p><strong>Semester:</strong> ${student.semester}</p>
      <p><strong>Email:</strong> ${student.email}</p>
      <p><strong>City:</strong> ${student.city}</p>
    </article>`;
}

function createFaqCard(faq) {
  return `
    <article class="card">
      <span class="tag">${faq.category}</span>
      <h3>${faq.question}</h3>
      <p>${faq.answer}</p>
    </article>`;
}

export function renderCards(records, type) {
  if (records.length === 0) {
    resultsContainer.innerHTML = '<article class="card empty-card">No matching records found.</article>';
    return;
  }

  const cardFunctions = { events: createEventCard, students: createStudentCard, faqs: createFaqCard };
  resultsContainer.innerHTML = records.map(cardFunctions[type]).join("");
}

export function renderPagination(currentPage, totalPages) {
  if (totalPages <= 1) {
    paginationContainer.innerHTML = "";
    return;
  }

  const pageButtons = Array.from({ length: totalPages }, (_, index) => {
    const page = index + 1;
    const activeClass = page === currentPage ? "active" : "";
    return `<button class="page-button ${activeClass}" type="button" data-page="${page}">${page}</button>`;
  }).join("");

  paginationContainer.innerHTML = `
    <button class="page-button" type="button" data-page="previous" ${currentPage === 1 ? "disabled" : ""}>Previous</button>
    ${pageButtons}
    <button class="page-button" type="button" data-page="next" ${currentPage === totalPages ? "disabled" : ""}>Next</button>`;
}

export function showLoading(show) {
  document.getElementById("loading-message").textContent = show ? "Loading data..." : "";
}

export function showError(message = "") {
  document.getElementById("error-message").textContent = message;
}

export function updateResultCount(count) {
  document.getElementById("result-count").textContent = `${count} result${count === 1 ? "" : "s"}`;
}
