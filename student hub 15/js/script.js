function showPage(pageId) {
    // 1. Hide all text content blocks
    const pages = document.querySelectorAll('.page-content');
    pages.forEach(page => {
        page.classList.remove('active-page');
    });

    // 2. Remove the highlight styling from all navigation tabs
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.classList.remove('active');
    });

    // 3. Make the selected content page visible
    const targetPage = document.getElementById(pageId);
    if (targetPage) {
        targetPage.classList.add('active-page');
    }

    // 4. Add highlight styling to the clicked tab
    if (window.event && window.event.currentTarget) {
        window.event.currentTarget.classList.add('active');
    }
}

// Student registration form validation & JSON persistence
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('registration-form');

    // Stop if this is not the register page.
    if (!form) {
        return;
    }

    var nameInput = document.getElementById('reg-name');
    var emailInput = document.getElementById('reg-email');
    var mobileInput = document.getElementById('reg-mobile');
    var courseInput = document.getElementById('reg-course');
    var yearInput = document.getElementById('reg-year');
    var passwordInput = document.getElementById('reg-password');
    var confirmInput = document.getElementById('reg-confirm');
    var termsInput = document.getElementById('reg-terms');
    var genderInputs = document.querySelectorAll('input[name="gender"]');
    var successMessage = document.getElementById('form-success');
    var strengthMessage = document.getElementById('password-strength');

    // Regex patterns required for name, email and mobile number.
    var nameRegex = /^[A-Za-z]+(?:[ '-][A-Za-z]+)*$/;
    var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    var mobileRegex = /^[6-9][0-9]{9}$/;

    function showError(input, errorId, message) {
        var error = document.getElementById(errorId);
        if (error) {
            error.textContent = message;
        }
        if (input) {
            input.classList.add('input-error');
            input.classList.remove('input-valid');
            input.setAttribute('aria-invalid', 'true');
        }
        return false;
    }

    function clearError(input, errorId) {
        var error = document.getElementById(errorId);
        if (error) {
            error.textContent = '';
        }
        if (input) {
            input.classList.remove('input-error');
            input.classList.add('input-valid');
            input.setAttribute('aria-invalid', 'false');
        }
        return true;
    }

    function validateName() {
        var value = nameInput.value.trim();
        if (!value) {
            return showError(nameInput, 'name-error', 'Please enter your full name.');
        }
        if (!nameRegex.test(value)) {
            return showError(nameInput, 'name-error', 'Use letters, spaces, apostrophes, or hyphens only.');
        }
        return clearError(nameInput, 'name-error');
    }

    function validateEmail() {
        var value = emailInput.value.trim();
        if (!value) {
            return showError(emailInput, 'email-error', 'Please enter your email address.');
        }
        if (!emailRegex.test(value)) {
            return showError(emailInput, 'email-error', 'Enter a valid email address, for example name@example.com.');
        }
        return clearError(emailInput, 'email-error');
    }

    function validateMobile() {
        var value = mobileInput.value.trim();
        if (!value) {
            return showError(mobileInput, 'mobile-error', 'Please enter your mobile number.');
        }
        if (!mobileRegex.test(value)) {
            return showError(mobileInput, 'mobile-error', 'Enter a valid 10-digit mobile number starting with 6, 7, 8, or 9.');
        }
        return clearError(mobileInput, 'mobile-error');
    }

    function validateSelect(input, errorId, label) {
        if (!input.value) {
            return showError(input, errorId, 'Please select your ' + label + '.');
        }
        return clearError(input, errorId);
    }

    function passwordStrength(value) {
        var score = 0;
        if (value.length >= 8) { score++; }
        if (/[a-z]/.test(value)) { score++; }
        if (/[A-Z]/.test(value)) { score++; }
        if (/[0-9]/.test(value)) { score++; }
        if (/[^A-Za-z0-9]/.test(value)) { score++; }

        if (!value) { return 'Not entered'; }
        if (score <= 2) { return 'Weak'; }
        if (score <= 4) { return 'Medium'; }
        return 'Strong';
    }

    function updatePasswordStrength() {
        if (!strengthMessage) return;
        var strength = passwordStrength(passwordInput.value);
        strengthMessage.textContent = 'Password strength: ' + strength;
        strengthMessage.className = 'password-strength';
        if (strength !== 'Not entered') {
            strengthMessage.classList.add('strength-' + strength.toLowerCase());
        }
        return strength;
    }

    function validatePassword() {
        var value = passwordInput.value;
        var strength = updatePasswordStrength();
        if (!value) {
            return showError(passwordInput, 'password-error', 'Please create a password.');
        }
        if (value.length < 8 || strength === 'Weak') {
            return showError(passwordInput, 'password-error', 'Use at least 8 characters and combine letters with numbers or symbols.');
        }
        return clearError(passwordInput, 'password-error');
    }

    function validateConfirmPassword() {
        if (!confirmInput.value) {
            return showError(confirmInput, 'confirm-error', 'Please confirm your password.');
        }
        if (confirmInput.value !== passwordInput.value) {
            return showError(confirmInput, 'confirm-error', 'Passwords do not match.');
        }
        return clearError(confirmInput, 'confirm-error');
    }

    function validateGender() {
        var selected = document.querySelector('input[name="gender"]:checked');
        var error = document.getElementById('gender-error');
        if (!selected) {
            if (error) error.textContent = 'Please select your gender.';
            genderInputs.forEach(function (input) {
                input.setAttribute('aria-invalid', 'true');
            });
            return false;
        }
        if (error) error.textContent = '';
        genderInputs.forEach(function (input) {
            input.setAttribute('aria-invalid', 'false');
        });
        return true;
    }

    function validateTerms() {
        if (!termsInput.checked) {
            return showError(termsInput, 'terms-error', 'Please accept the terms and conditions.');
        }
        return clearError(termsInput, 'terms-error');
    }

    function hideSuccessMessage() {
        if (successMessage) {
            successMessage.hidden = true;
            successMessage.textContent = '';
        }
    }

    nameInput.addEventListener('input', function () { validateName(); hideSuccessMessage(); });
    emailInput.addEventListener('input', function () { validateEmail(); hideSuccessMessage(); });
    mobileInput.addEventListener('input', function () { validateMobile(); hideSuccessMessage(); });
    courseInput.addEventListener('change', function () { validateSelect(courseInput, 'course-error', 'course'); hideSuccessMessage(); });
    yearInput.addEventListener('change', function () { validateSelect(yearInput, 'year-error', 'year'); hideSuccessMessage(); });
    passwordInput.addEventListener('input', function () {
        validatePassword();
        if (confirmInput.value) { validateConfirmPassword(); }
        hideSuccessMessage();
    });
    confirmInput.addEventListener('input', function () { validateConfirmPassword(); hideSuccessMessage(); });
    termsInput.addEventListener('change', function () { validateTerms(); hideSuccessMessage(); });

    genderInputs.forEach(function (input) {
        input.addEventListener('change', function () { validateGender(); hideSuccessMessage(); });
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();

        // Run every validation so the user can see all fields that need attention.
        var nameIsValid = validateName();
        var emailIsValid = validateEmail();
        var mobileIsValid = validateMobile();
        var courseIsValid = validateSelect(courseInput, 'course-error', 'course');
        var yearIsValid = validateSelect(yearInput, 'year-error', 'year');
        var genderIsValid = validateGender();
        var passwordIsValid = validatePassword();
        var confirmIsValid = validateConfirmPassword();
        var termsAreValid = validateTerms();
        var isValid = nameIsValid && emailIsValid && mobileIsValid && courseIsValid &&
            yearIsValid && genderIsValid && passwordIsValid && confirmIsValid && termsAreValid;

        if (isValid) {
            // Construct the student registration payload
            var selectedGender = document.querySelector('input[name="gender"]:checked');
            var genderValue = selectedGender ? selectedGender.value : 'Not Specified';
            var courseVal = courseInput.value;
            var yearVal = yearInput.value;

            var submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Processing with PHP...';
            }

            var formData = new FormData(form);

            // Attempt PHP backend submission via Fetch API
            var isHttp = window.location.protocol.startsWith('http');
            var phpEndpoint = form.getAttribute('data-endpoint') ||
                (window.location.pathname.includes('/pages/') ? '../php/process_register.php' : 'php/process_register.php');

            if (isHttp) {
                fetch(phpEndpoint, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Register Account';
                    }

                    if (data.success) {
                        var studentRecord = data.data && data.data.student ? data.data.student : {
                            id: "STU-9999",
                            name: nameInput.value.trim(),
                            email: emailInput.value.trim(),
                            mobile: mobileInput.value.trim(),
                            course: courseVal,
                            year: yearVal,
                            gender: genderValue
                        };
                        studentRecord.password = passwordInput.value;

                        // Sync to localStorage
                        try {
                            localStorage.setItem('student_hub_current_user', JSON.stringify(studentRecord));
                            localStorage.setItem('student_hub_logged_in', 'true');
                            localStorage.removeItem('student_hub_is_admin');
                            var existing = JSON.parse(localStorage.getItem('student_hub_students') || '[]');
                            var filtered = existing.filter(function(s) { return s.email.toLowerCase() !== studentRecord.email.toLowerCase(); });
                            filtered.unshift(studentRecord);
                            localStorage.setItem('student_hub_students', JSON.stringify(filtered));
                        } catch (e) {
                            console.error("Local storage error:", e);
                        }

                        if (successMessage) {
                            successMessage.innerHTML = `<strong>Server Success:</strong> ${data.message}<br><small>Record added to <code>data/students.json</code> (ID: ${studentRecord.id})</small>`;
                            successMessage.hidden = false;
                            successMessage.className = "alert alert-success";
                            successMessage.style.display = "block";
                            successMessage.style.padding = "1rem";
                            successMessage.style.marginTop = "1rem";
                            successMessage.style.borderRadius = "6px";
                            successMessage.style.backgroundColor = "#dcfce7";
                            successMessage.style.color = "#15803d";
                            successMessage.style.border = "1px solid #86efac";
                        }

                        setTimeout(function () {
                            window.location.href = "profile.html";
                        }, 1200);

                    } else {
                        // Display server-side errors
                        if (data.errors) {
                            if (data.errors.fullname) showError(nameInput, 'name-error', data.errors.fullname);
                            if (data.errors.email) showError(emailInput, 'email-error', data.errors.email);
                            if (data.errors.mobile) showError(mobileInput, 'mobile-error', data.errors.mobile);
                            if (data.errors.course) showError(courseInput, 'course-error', data.errors.course);
                            if (data.errors.year) showError(yearInput, 'year-error', data.errors.year);
                            if (data.errors.gender) showError(null, 'gender-error', data.errors.gender);
                            if (data.errors.password) showError(passwordInput, 'password-error', data.errors.password);
                            if (data.errors.confirm_password) showError(confirmInput, 'confirm-error', data.errors.confirm_password);
                            if (data.errors.terms) showError(termsInput, 'terms-error', data.errors.terms);
                        }

                        if (successMessage) {
                            successMessage.innerHTML = `<strong>Server Validation Error:</strong> ${data.message}`;
                            successMessage.hidden = false;
                            successMessage.className = "alert alert-danger";
                            successMessage.style.display = "block";
                            successMessage.style.padding = "0.75rem";
                            successMessage.style.marginTop = "1rem";
                            successMessage.style.borderRadius = "6px";
                            successMessage.style.backgroundColor = "#fee2e2";
                            successMessage.style.color = "#b91c1c";
                            successMessage.style.border = "1px solid #fca5a5";
                        }
                    }
                })
                .catch(function (err) {
                    console.warn("PHP endpoint fetch failed, falling back:", err);
                    fallbackLocalSave();
                });
            } else {
                fallbackLocalSave();
            }

            function fallbackLocalSave() {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Register Account';
                }

                var dept = "Computer Science";
                if (courseVal === "BCA") dept = "Computer Applications";
                else if (courseVal === "BBA") dept = "Business Administration";
                else if (courseVal === "Other") dept = "General Studies";

                var semesterVal = "3";
                if (yearVal === "First Year") semesterVal = "1";
                else if (yearVal === "Second Year") semesterVal = "3";
                else if (yearVal === "Third Year") semesterVal = "5";
                else if (yearVal === "Final Year") semesterVal = "7";

                var studentId = "STU-" + Math.floor(1000 + Math.random() * 9000);
                var studentRecord = {
                    id: studentId,
                    name: nameInput.value.trim(),
                    email: emailInput.value.trim(),
                    mobile: mobileInput.value.trim(),
                    password: passwordInput.value,
                    course: courseVal,
                    year: yearVal,
                    gender: genderValue,
                    department: dept,
                    semester: semesterVal,
                    city: "Campus Resident",
                    registeredDate: new Date().toISOString().split('T')[0],
                    bio: `Enrolled in ${courseVal} (${yearVal}). Passionate learner and active student hub member.`,
                    skills: ["Web Development", "Problem Solving", "Collaboration", "Public Speaking"]
                };

                try {
                    localStorage.setItem('student_hub_current_user', JSON.stringify(studentRecord));
                    localStorage.setItem('student_hub_logged_in', 'true');
                    localStorage.removeItem('student_hub_is_admin');
                    var existingStudents = JSON.parse(localStorage.getItem('student_hub_students') || '[]');
                    var filtered = existingStudents.filter(function(s) { return s.email.toLowerCase() !== studentRecord.email.toLowerCase(); });
                    filtered.unshift(studentRecord);
                    localStorage.setItem('student_hub_students', JSON.stringify(filtered));
                } catch (err) {
                    console.error("Could not persist student record:", err);
                }

                if (successMessage) {
                    successMessage.innerHTML = `<strong>Registration Successful!</strong> Saved to JSON records.<br>Redirecting to your Profile page...`;
                    successMessage.hidden = false;
                    successMessage.className = "alert alert-success";
                    successMessage.style.display = "block";
                    successMessage.style.padding = "1rem";
                    successMessage.style.marginTop = "1rem";
                    successMessage.style.borderRadius = "6px";
                    successMessage.style.backgroundColor = "#dcfce7";
                    successMessage.style.color = "#15803d";
                    successMessage.style.border = "1px solid #86efac";
                }

                setTimeout(function () {
                    window.location.href = "profile.html";
                }, 1200);
            }

        } else {
            hideSuccessMessage();
            var firstInvalidInput = form.querySelector('.input-error, input[aria-invalid="true"]');
            if (firstInvalidInput) {
                firstInvalidInput.focus();
            }
        }
    });
});
