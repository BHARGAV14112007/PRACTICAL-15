/**
 * Profile Page Controller (Student Private Portal)
 * Allows the authenticated student to view and edit only their OWN personal profile data
 * and registered events. Student directory access is restricted to Administrators only.
 */

// Load and render the current logged-in user profile
async function loadCurrentProfile() {
    let currentUser = null;
    const storedUserJson = localStorage.getItem('student_hub_current_user');

    if (storedUserJson) {
        try {
            currentUser = JSON.parse(storedUserJson);
        } catch (e) {
            console.error('Error parsing stored user JSON:', e);
        }
    }

    // If no user is in session, load initial default
    if (!currentUser) {
        try {
            const res = await fetch('../data/students.json');
            const data = await res.json();
            if (data && data.length > 0) {
                currentUser = {
                    ...data[0],
                    course: data[0].course || 'B.Tech',
                    year: data[0].year || 'Third Year',
                    mobile: data[0].mobile || '+91 9876543210',
                    gender: data[0].gender || 'Male',
                    bio: data[0].bio || 'Enrolled student exploring software engineering and web technologies.',
                    skills: data[0].skills || ['HTML5', 'CSS3', 'JavaScript', 'Python']
                };
                localStorage.setItem('student_hub_current_user', JSON.stringify(currentUser));
            }
        } catch (e) {
            console.warn('Could not fetch default student:', e);
        }
    }

    if (currentUser) {
        renderProfileData(currentUser);
    }

    // Load registered events for this individual user
    loadRegisteredEvents();
}

function renderProfileData(user) {
    // Top summary
    const nameEls = document.querySelectorAll('.profile-name-display');
    nameEls.forEach(el => el.textContent = user.name || 'Student Name');

    const courseEls = document.querySelectorAll('.profile-course-display');
    courseEls.forEach(el => {
        el.textContent = `${user.course || 'B.Tech'} in ${user.department || 'Computer Science'} (${user.year || '3rd Year'})`;
    });

    // ID Badge
    const idEl = document.getElementById('profile-id-display');
    if (idEl) idEl.textContent = user.id || 'STU-1001';

    // Info fields
    const emailEl = document.getElementById('profile-email-display');
    if (emailEl) emailEl.textContent = user.email || 'student@university.edu';

    const mobileEl = document.getElementById('profile-mobile-display');
    if (mobileEl) mobileEl.textContent = user.mobile || '+91 9876543210';

    const deptEl = document.getElementById('profile-dept-display');
    if (deptEl) deptEl.textContent = user.department || 'Computer Science';

    const semEl = document.getElementById('profile-sem-display');
    if (semEl) semEl.textContent = `Semester ${user.semester || '3'}`;

    const genderEl = document.getElementById('profile-gender-display');
    if (genderEl) genderEl.textContent = user.gender || 'Not Specified';

    const cityEl = document.getElementById('profile-city-display');
    if (cityEl) cityEl.textContent = user.city || 'Campus Resident';

    const dateEl = document.getElementById('profile-date-display');
    if (dateEl) dateEl.textContent = user.registeredDate || new Date().toISOString().split('T')[0];

    const bioEl = document.getElementById('profile-bio-display');
    if (bioEl) bioEl.textContent = user.bio || `Student enrolled in ${user.course || 'B.Tech'} (${user.year || 'Third Year'}).`;

    // Avatar initials
    const initials = (user.name || 'S H').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
    const avatarTextEls = document.querySelectorAll('.profile-avatar-initials');
    avatarTextEls.forEach(el => el.textContent = initials);

    // Skills
    const skillsContainer = document.getElementById('profile-skills-display');
    if (skillsContainer && user.skills && Array.isArray(user.skills)) {
        skillsContainer.innerHTML = user.skills.map(s => 
            `<span style="display: inline-block; background: var(--primary-light); color: var(--primary-color); font-weight: 600; font-size: 0.8rem; padding: 0.3rem 0.75rem; border-radius: 20px; margin-right: 0.5rem; margin-bottom: 0.5rem;">${s}</span>`
        ).join('');
    }

    // Update global header/sidebar name displays
    const navUserNames = document.querySelectorAll('.user-display-name');
    navUserNames.forEach(el => el.textContent = user.name || 'Student');

    const navUserRoles = document.querySelectorAll('.user-display-role');
    navUserRoles.forEach(el => el.textContent = `${user.course || 'B.Tech'} · ${user.year || '3rd Year'}`);
}

async function loadRegisteredEvents() {
    const regContainer = document.getElementById('profile-registered-events');
    if (!regContainer) return;

    try {
        const storedIds = JSON.parse(localStorage.getItem('student_hub_registered_events') || '[]');
        
        let allEvents = [];
        const localEvents = localStorage.getItem('student_hub_events');
        if (localEvents) {
            allEvents = JSON.parse(localEvents);
        } else {
            const res = await fetch('../data/events.json');
            allEvents = await res.json();
        }

        const registeredEvents = allEvents.filter(e => storedIds.includes(e.id));

        if (registeredEvents.length === 0) {
            regContainer.innerHTML = `
                <div style="text-align: center; padding: 2rem; border: 1px dashed var(--border-color); border-radius: 8px; color: var(--text-muted);">
                    <p style="margin-bottom: 0.75rem;">You have not registered for any campus events yet.</p>
                    <a href="event.html" class="btn btn-sm btn-primary">Browse & Register for Events</a>
                </div>`;
        } else {
            regContainer.innerHTML = registeredEvents.map(event => `
                <div class="card mb-2" style="padding: 1rem 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; border-left: 4px solid var(--primary-color);">
                    <div>
                        <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; color: var(--primary-color); background: var(--primary-light); padding: 0.15rem 0.5rem; border-radius: 4px;">
                            ${event.category}
                        </span>
                        <h4 style="font-size: 1.05rem; font-weight: 700; color: var(--text-main); margin: 0.35rem 0 0.2rem;">
                            ${event.title}
                        </h4>
                        <p style="font-size: 0.85rem; color: var(--text-muted); margin: 0;">
                            📅 ${event.date} · 📍 ${event.venue} · 👤 ${event.organizer}
                        </p>
                    </div>
                    <span style="font-size: 0.8rem; font-weight: 600; color: #16a34a; background: #dcfce7; padding: 0.3rem 0.75rem; border-radius: 20px;">
                        ✓ Confirmed
                    </span>
                </div>
            `).join('');
        }
    } catch (e) {
        console.error('Error loading registered events:', e);
    }
}

// Download personal profile JSON
window.downloadProfileJson = function () {
    const storedUserJson = localStorage.getItem('student_hub_current_user');
    if (!storedUserJson) {
        alert('No profile data available to download.');
        return;
    }

    const blob = new Blob([storedUserJson], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `student_profile_${JSON.parse(storedUserJson).id || 'data'}.json`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
};

// Edit Profile Modal Handling
window.openEditProfileModal = function () {
    const stored = localStorage.getItem('student_hub_current_user');
    if (!stored) return;
    const user = JSON.parse(stored);

    document.getElementById('edit-name').value = user.name || '';
    document.getElementById('edit-email').value = user.email || '';
    document.getElementById('edit-mobile').value = user.mobile || '';
    document.getElementById('edit-course').value = user.course || 'B.Tech';
    document.getElementById('edit-year').value = user.year || 'Third Year';
    document.getElementById('edit-dept').value = user.department || 'Computer Science';
    document.getElementById('edit-city').value = user.city || '';
    document.getElementById('edit-bio').value = user.bio || '';
    document.getElementById('edit-skills').value = (user.skills || []).join(', ');

    const modal = document.getElementById('edit-profile-modal');
    if (modal) modal.style.display = 'flex';
};

window.closeEditProfileModal = function () {
    const modal = document.getElementById('edit-profile-modal');
    if (modal) modal.style.display = 'none';
};

window.saveProfileChanges = function (e) {
    if (e) e.preventDefault();

    const stored = localStorage.getItem('student_hub_current_user') || '{}';
    const existing = JSON.parse(stored);

    const skillsRaw = document.getElementById('edit-skills').value;
    const skillsArr = skillsRaw.split(',').map(s => s.trim()).filter(Boolean);

    const updatedUser = {
        ...existing,
        name: document.getElementById('edit-name').value.trim() || existing.name,
        email: document.getElementById('edit-email').value.trim() || existing.email,
        mobile: document.getElementById('edit-mobile').value.trim() || existing.mobile,
        course: document.getElementById('edit-course').value,
        year: document.getElementById('edit-year').value,
        department: document.getElementById('edit-dept').value.trim() || existing.department,
        city: document.getElementById('edit-city').value.trim() || existing.city,
        bio: document.getElementById('edit-bio').value.trim() || existing.bio,
        skills: skillsArr.length > 0 ? skillsArr : existing.skills
    };

    localStorage.setItem('student_hub_current_user', JSON.stringify(updatedUser));
    
    // Also update in registered students list if present
    try {
        const studentsList = JSON.parse(localStorage.getItem('student_hub_students') || '[]');
        const idx = studentsList.findIndex(s => s.email && s.email.toLowerCase() === updatedUser.email.toLowerCase());
        if (idx !== -1) {
            studentsList[idx] = { ...studentsList[idx], ...updatedUser };
            localStorage.setItem('student_hub_students', JSON.stringify(studentsList));
        }
    } catch (err) {
        console.warn('Error updating student list:', err);
    }

    renderProfileData(updatedUser);
    closeEditProfileModal();

    // Show temporary feedback toast
    const alertBox = document.getElementById('profile-saved-toast');
    if (alertBox) {
        alertBox.style.display = 'block';
        setTimeout(() => { alertBox.style.display = 'none'; }, 3000);
    }
};

// Initialization on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    loadCurrentProfile();

    const editForm = document.getElementById('edit-profile-form');
    if (editForm) {
        editForm.addEventListener('submit', window.saveProfileChanges);
    }
});
