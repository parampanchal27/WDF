const THEME_KEY = "studenthub_theme";

function applyTheme(theme) {
    const isDark = theme === "dark";
    document.body.classList.toggle("dark-theme", isDark);
    const button = document.getElementById("themeToggle");

    if (button) {
        button.textContent = isDark ? "☀️ Light Mode" : "🌙 Dark Mode";
        button.setAttribute("aria-label", isDark ? "Switch to light mode" : "Switch to dark mode");
    }

    localStorage.setItem(THEME_KEY, theme);
}

const savedTheme = localStorage.getItem(THEME_KEY) || "light";
applyTheme(savedTheme);

const themeToggle = document.getElementById("themeToggle");
if (themeToggle) {
    themeToggle.addEventListener("click", function () {
        const currentTheme = document.body.classList.contains("dark-theme") ? "dark" : "light";
        applyTheme(currentTheme === "dark" ? "light" : "dark");
    });
}

const form = document.getElementById("registerForm");
const successMessage = document.getElementById("successMessage");

function setError(id, message) {
    const field = document.getElementById(id);
    const error = document.getElementById(id + "Error");

    if (field) {
        field.classList.add("field-invalid");
        field.setAttribute("aria-invalid", "true");
    }
    if (error) {
        error.textContent = message;
    }
}

function clearError(id) {
    const field = document.getElementById(id);
    const error = document.getElementById(id + "Error");

    if (field) {
        field.classList.remove("field-invalid");
        field.setAttribute("aria-invalid", "false");
    }
    if (error) {
        error.textContent = "";
    }
}

if (form) {
    const passwordField = document.getElementById("password");
    const passwordHint = document.getElementById("passwordHint");

    function updatePasswordStrength() {
        const password = passwordField.value;
        let score = 0;
        if (password.length >= 8) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        const labels = ["Use 8+ characters with a number and symbol.", "Weak password.", "Fair password.", "Good password.", "Strong password."];
        passwordHint.textContent = labels[score];
        passwordHint.dataset.strength = String(score);
    }

    function validateRegistration() {
        const fullName = document.getElementById("fullName").value.trim();
        const username = document.getElementById("username").value.trim();
        const email = document.getElementById("email").value.trim();
        const mobile = document.getElementById("mobile").value.trim();
        const password = passwordField.value;
        const confirmPassword = document.getElementById("confirmPassword").value;
        const genderSelected = form.querySelector("input[name='gender']:checked");
        let isValid = true;

        const checks = [
            ["fullName", fullName.length >= 2, "Please enter your full name."],
            ["username", username.length >= 3, "Username must be at least 3 characters."],
            ["email", /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email), "Please enter a valid email."],
            ["mobile", /^\d{10}$/.test(mobile), "Enter a valid 10-digit mobile number."],
            ["course", document.getElementById("course").value !== "", "Please select your course."],
            ["year", document.getElementById("year").value !== "", "Please select your year or semester."],
            ["password", password.length >= 8 && /\d/.test(password) && /[^A-Za-z0-9]/.test(password), "Use at least 8 characters, including a number and symbol."],
            ["confirmPassword", confirmPassword !== "" && password === confirmPassword, "Passwords must match."]
        ];

        checks.forEach(function (check) {
            if (check[1]) clearError(check[0]);
            else { setError(check[0], check[2]); isValid = false; }
        });

        if (genderSelected) clearError("gender");
        else { setError("gender", "Please select your gender."); isValid = false; }

        const terms = document.getElementById("terms");
        if (terms.checked) clearError("terms");
        else { setError("terms", "You must accept the terms and conditions."); isValid = false; }

        return isValid;
    }

    passwordField.addEventListener("input", updatePasswordStrength);
    form.querySelectorAll("input, select").forEach(function (field) {
        field.addEventListener("input", validateRegistration);
        field.addEventListener("change", validateRegistration);
    });
    form.addEventListener("submit", function (event) {
        event.preventDefault();
        if (validateRegistration()) {
            successMessage.textContent = "Registration successful!";
            form.reset();
            updatePasswordStrength();
        } else {
            successMessage.textContent = "Please correct the highlighted fields.";
        }
    });
}

function setupMobileNavigation() {
    const header = document.querySelector(".header");
    const navigation = document.querySelector(".indexnav");

    if (!header || !navigation || document.getElementById("menuToggle")) {
        return;
    }

    const menuButton = document.createElement("button");
    menuButton.id = "menuToggle";
    menuButton.className = "menu-toggle";
    menuButton.type = "button";
    menuButton.setAttribute("aria-label", "Open navigation menu");
    menuButton.setAttribute("aria-expanded", "false");
    menuButton.textContent = "☰";
    header.insertBefore(menuButton, navigation);

    menuButton.addEventListener("click", function () {
        const isOpen = header.classList.toggle("menu-open");
        menuButton.setAttribute("aria-expanded", String(isOpen));
        menuButton.setAttribute("aria-label", isOpen ? "Close navigation menu" : "Open navigation menu");
    });
}

function setupFaq() {
    document.querySelectorAll(".faq-question").forEach(function (question) {
        question.addEventListener("click", function () {
            const answer = document.getElementById(question.getAttribute("aria-controls"));
            const isOpen = question.getAttribute("aria-expanded") === "true";
            question.setAttribute("aria-expanded", String(!isOpen));
            if (answer) {
                answer.hidden = isOpen;
            }
        });
    });
}

function setupNotifications() {
    document.querySelectorAll("[data-dismiss]").forEach(function (button) {
        button.addEventListener("click", function () {
            const target = document.getElementById(button.getAttribute("data-dismiss"));
            if (target) {
                target.hidden = true;
            }
        });
    });
}

function setupModal() {
    document.querySelectorAll("[data-modal-open]").forEach(function (openButton) {
        const modal = document.getElementById(openButton.getAttribute("data-modal-open"));
        if (!modal) {
            return;
        }

        const closeModal = function () {
            modal.hidden = true;
            openButton.focus();
        };

        openButton.addEventListener("click", function () {
            modal.hidden = false;
            const closeButton = modal.querySelector("[data-modal-close]");
            if (closeButton) {
                closeButton.focus();
            }
        });

        modal.querySelectorAll("[data-modal-close]").forEach(function (closeButton) {
            closeButton.addEventListener("click", closeModal);
        });

        modal.addEventListener("click", function (event) {
            if (event.target === modal) {
                closeModal();
            }
        });
    });
}

function setupSlider() {
    document.querySelectorAll(".content-slider").forEach(function (slider) {
        const slides = Array.from(slider.querySelectorAll(".slide"));
        let currentSlide = 0;

        if (slides.length < 2) {
            return;
        }

        const showSlide = function (index) {
            currentSlide = (index + slides.length) % slides.length;
            slides.forEach(function (slide, slideIndex) {
                slide.hidden = slideIndex !== currentSlide;
            });
        };

        slider.querySelector(".slider-prev").addEventListener("click", function () {
            showSlide(currentSlide - 1);
        });
        slider.querySelector(".slider-next").addEventListener("click", function () {
            showSlide(currentSlide + 1);
        });
        showSlide(0);
    });
}

setupMobileNavigation();
setupFaq();
setupNotifications();
setupModal();
setupSlider();
