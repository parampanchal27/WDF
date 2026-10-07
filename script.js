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

function updateAuthenticationNavigation() {
    const navigation = document.querySelector(".indexnav");
    if (!navigation) {
        return;
    }

    let faqLink = navigation.querySelector("a[data-faq-link], a[href$='faq.html']");
    if (!faqLink) {
        faqLink = document.createElement("a");
        faqLink.href = "faq.html";
        faqLink.textContent = "FAQ";
        faqLink.dataset.faqLink = "true";
        navigation.appendChild(faqLink);
    }
    faqLink.dataset.faqLink = "true";

    let logoutLink = navigation.querySelector("a[data-logout-link], a[href*='logout.php']");
    if (!logoutLink) {
        logoutLink = document.createElement("a");
        logoutLink.href = "practical7/logout.php";
        logoutLink.textContent = "Logout";
        logoutLink.dataset.logoutLink = "true";
        navigation.appendChild(logoutLink);
    }
    logoutLink.dataset.logoutLink = "true";

    fetch("practical7/session_status.php", { credentials: "same-origin" })
        .then(function (response) {
            if (!response.ok) {
                throw new Error("Unable to check login status.");
            }
            return response.json();
        })
        .then(function (status) {
            const welcomeHeading = document.getElementById("welcomeHeading");
            if (welcomeHeading) {
                if (status.authenticated && status.user && status.user.fullName) {
                    welcomeHeading.textContent = "Welcome back, " + status.user.fullName;
                } else {
                    welcomeHeading.textContent = "Welcome to StudentHub";
                }
            }

            const loginLink = Array.from(navigation.querySelectorAll("a")).find(function (link) {
                return link.textContent.trim().toLowerCase() === "login";
            });

            if (loginLink) {
                loginLink.hidden = status.authenticated;
            }
            logoutLink.hidden = !status.authenticated;
        })
        .catch(function (error) {
            console.error(error);
            logoutLink.hidden = true;
        });
}

updateAuthenticationNavigation();

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
        if (validateRegistration()) {
            successMessage.textContent = "Submitting registration...";
        } else {
            event.preventDefault();
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
    const faqList = document.getElementById("faqList");
    const faqSearch = document.getElementById("faqSearch");
    const faqCategory = document.getElementById("faqCategory");
    const faqSort = document.getElementById("faqSort");
    const faqStatus = document.getElementById("faqStatus");
    const faqPrev = document.getElementById("faqPrev");
    const faqNext = document.getElementById("faqNext");
    const faqPageInfo = document.getElementById("faqPageInfo");

    if (!faqList) {
        return;
    }

    let faqData = [];
    let searchText = "";
    let selectedCategory = "all";
    let sortMode = "default";
    const itemsPerPage = 5;
    let currentPage = 1;

    function renderFaqs(items) {
        if (!faqList) return;

        faqList.innerHTML = "";

        if (!items.length) {
            faqList.innerHTML = '<p class="faq-empty">No matching questions found.</p>';
            faqPageInfo.textContent = "Page 0 of 0";
            faqPrev.disabled = true;
            faqNext.disabled = true;
            return;
        }

        const totalPages = Math.max(1, Math.ceil(items.length / itemsPerPage));
        if (currentPage > totalPages) {
            currentPage = totalPages;
        }

        const start = (currentPage - 1) * itemsPerPage;
        const visibleItems = items.slice(start, start + itemsPerPage);

        visibleItems.forEach(function (item) {
            const article = document.createElement("article");
            article.className = "faq-item";

            const categoryBadge = document.createElement("span");
            categoryBadge.className = "faq-item-meta";
            categoryBadge.textContent = item.category;

            const question = document.createElement("h3");
            const button = document.createElement("button");
            button.type = "button";
            button.className = "faq-question";
            button.setAttribute("aria-expanded", "false");
            button.setAttribute("aria-controls", "faq-answer-" + item.id);
            button.textContent = item.question;

            button.addEventListener("click", function () {
                const answer = document.getElementById("faq-answer-" + item.id);
                const isOpen = button.getAttribute("aria-expanded") === "true";
                button.setAttribute("aria-expanded", String(!isOpen));
                if (answer) {
                    answer.hidden = isOpen;
                }
            });

            const answer = document.createElement("div");
            answer.id = "faq-answer-" + item.id;
            answer.className = "faq-answer";
            answer.hidden = true;
            answer.textContent = item.answer;

            question.appendChild(button);
            article.appendChild(categoryBadge);
            article.appendChild(question);
            article.appendChild(answer);
            faqList.appendChild(article);
        });

        faqPageInfo.textContent = "Page " + currentPage + " of " + totalPages;
        faqPrev.disabled = currentPage === 1;
        faqNext.disabled = currentPage === totalPages;
    }

    function updateFaqs() {
        const filtered = faqData.filter(function (item) {
            const matchesSearch = item.question.toLowerCase().includes(searchText.toLowerCase()) || item.answer.toLowerCase().includes(searchText.toLowerCase());
            const matchesCategory = selectedCategory === "all" || item.category === selectedCategory;
            return matchesSearch && matchesCategory;
        });

        const sorted = [...filtered].sort(function (a, b) {
            if (sortMode === "question-asc") return a.question.localeCompare(b.question);
            if (sortMode === "question-desc") return b.question.localeCompare(a.question);
            if (sortMode === "category") return a.category.localeCompare(b.category) || a.question.localeCompare(b.question);
            return 0;
        });

        currentPage = 1;
        renderFaqs(sorted);

        if (faqStatus) {
            faqStatus.textContent = sorted.length + " question(s) found" + (selectedCategory !== "all" ? " in " + selectedCategory : "");
        }
    }

    function populateCategories() {
        if (!faqCategory) return;
        const categories = [...new Set(faqData.map(function (item) { return item.category; }))].sort();
        categories.forEach(function (category) {
            const option = document.createElement("option");
            option.value = category;
            option.textContent = category;
            faqCategory.appendChild(option);
        });
    }

    function loadFaqData() {
        fetch("faq-data.json")
            .then(function (response) {
                if (!response.ok) {
                    throw new Error("Failed to load FAQ data");
                }
                return response.json();
            })
            .then(function (data) {
                faqData = data.faqs || [];
                populateCategories();
                updateFaqs();
            })
            .catch(function (error) {
                console.error("FAQ fetch error:", error);
                if (faqStatus) {
                    faqStatus.textContent = "Unable to load FAQ data. Please try again later.";
                }
                if (faqList) {
                    faqList.innerHTML = '<p class="faq-empty">Unable to load questions right now.</p>';
                }
            });
    }

    if (faqSearch) {
        faqSearch.addEventListener("input", function (event) {
            searchText = event.target.value.trim();
            updateFaqs();
        });
    }

    if (faqCategory) {
        faqCategory.addEventListener("change", function (event) {
            selectedCategory = event.target.value;
            updateFaqs();
        });
    }

    if (faqSort) {
        faqSort.addEventListener("change", function (event) {
            sortMode = event.target.value;
            updateFaqs();
        });
    }

    if (faqPrev) {
        faqPrev.addEventListener("click", function () {
            if (currentPage > 1) {
                currentPage -= 1;
                const filtered = faqData.filter(function (item) {
                    const matchesSearch = item.question.toLowerCase().includes(searchText.toLowerCase()) || item.answer.toLowerCase().includes(searchText.toLowerCase());
                    const matchesCategory = selectedCategory === "all" || item.category === selectedCategory;
                    return matchesSearch && matchesCategory;
                });
                const sorted = [...filtered].sort(function (a, b) {
                    if (sortMode === "question-asc") return a.question.localeCompare(b.question);
                    if (sortMode === "question-desc") return b.question.localeCompare(a.question);
                    if (sortMode === "category") return a.category.localeCompare(b.category) || a.question.localeCompare(b.question);
                    return 0;
                });
                renderFaqs(sorted);
            }
        });
    }

    if (faqNext) {
        faqNext.addEventListener("click", function () {
            const filtered = faqData.filter(function (item) {
                const matchesSearch = item.question.toLowerCase().includes(searchText.toLowerCase()) || item.answer.toLowerCase().includes(searchText.toLowerCase());
                const matchesCategory = selectedCategory === "all" || item.category === selectedCategory;
                return matchesSearch && matchesCategory;
            });
            const sorted = [...filtered].sort(function (a, b) {
                if (sortMode === "question-asc") return a.question.localeCompare(b.question);
                if (sortMode === "question-desc") return b.question.localeCompare(a.question);
                if (sortMode === "category") return a.category.localeCompare(b.category) || a.question.localeCompare(b.question);
                return 0;
            });
            const totalPages = Math.max(1, Math.ceil(sorted.length / itemsPerPage));
            if (currentPage < totalPages) {
                currentPage += 1;
                renderFaqs(sorted);
            }
        });
    }

    loadFaqData();
}

function setupAnnouncementList() {
    const announcementList = document.getElementById("announcementList");
    const announcementSearch = document.getElementById("announcementSearch");
    const announcementCategory = document.getElementById("announcementCategory");
    const announcementStatus = document.getElementById("announcementStatus");

    if (!announcementList) {
        return;
    }

    let announcements = [];
    let searchTerm = "";
    let categoryFilter = "all";

    function renderAnnouncements(items) {
        announcementList.innerHTML = "";

        if (!items.length) {
            announcementList.innerHTML = '<p class="data-empty">No announcements match your search.</p>';
            if (announcementStatus) {
                announcementStatus.textContent = "0 announcements found.";
            }
            return;
        }

        items.forEach(function (item) {
            const card = document.createElement("article");
            card.className = "data-card";

            const header = document.createElement("div");
            header.className = "data-card-header";

            const title = document.createElement("h3");
            title.textContent = item.title;

            const badge = document.createElement("span");
            badge.className = "data-badge";
            badge.textContent = item.category;

            header.appendChild(title);
            header.appendChild(badge);

            const date = document.createElement("p");
            date.className = "data-date";
            date.textContent = "Date: " + item.date;

            const details = document.createElement("p");
            details.textContent = item.details;

            card.appendChild(header);
            card.appendChild(date);
            card.appendChild(details);
            announcementList.appendChild(card);
        });

        if (announcementStatus) {
            announcementStatus.textContent = items.length + " announcement(s) found.";
        }
    }

    function updateAnnouncements() {
        const filtered = announcements.filter(function (item) {
            const matchesSearch = item.title.toLowerCase().includes(searchTerm.toLowerCase()) || item.details.toLowerCase().includes(searchTerm.toLowerCase());
            const matchesCategory = categoryFilter === "all" || item.category === categoryFilter;
            return matchesSearch && matchesCategory;
        });

        renderAnnouncements(filtered);
    }

    function populateCategories() {
        if (!announcementCategory) return;
        const categories = [...new Set(announcements.map(function (item) { return item.category; }))].sort();
        categories.forEach(function (category) {
            const option = document.createElement("option");
            option.value = category;
            option.textContent = category;
            announcementCategory.appendChild(option);
        });
    }

    fetch("announcement-data.json")
        .then(function (response) {
            if (!response.ok) {
                throw new Error("Failed to load announcements");
            }
            return response.json();
        })
        .then(function (data) {
            announcements = data.announcements || [];
            populateCategories();
            updateAnnouncements();
        })
        .catch(function (error) {
            console.error("Announcement fetch error:", error);
            announcementList.innerHTML = '<p class="data-empty">Unable to load announcements right now.</p>';
            if (announcementStatus) {
                announcementStatus.textContent = "Unable to load announcements.";
            }
        });

    if (announcementSearch) {
        announcementSearch.addEventListener("input", function (event) {
            searchTerm = event.target.value.trim();
            updateAnnouncements();
        });
    }

    if (announcementCategory) {
        announcementCategory.addEventListener("change", function (event) {
            categoryFilter = event.target.value;
            updateAnnouncements();
        });
    }
}

function setupCourseList() {
    const courseList = document.getElementById("courseList");
    const courseSearch = document.getElementById("courseSearch");
    const courseFilter = document.getElementById("courseFilter");
    const courseStatus = document.getElementById("courseStatus");
    const courseCount = document.getElementById("courseCount");

    if (!courseList) {
        return;
    }

    let courses = [];
    let searchTerm = "";
    let codeFilter = "all";

    function renderCourses(items) {
        courseList.innerHTML = "";

        if (courseCount) {
            courseCount.textContent = items.length + " course" + (items.length === 1 ? "" : "s");
        }

        if (!items.length) {
            courseList.innerHTML = '<p class="data-empty">No courses match your search.</p>';
            if (courseStatus) {
                courseStatus.textContent = "0 courses found.";
            }
            return;
        }

        items.forEach(function (course) {
            const card = document.createElement("article");
            card.className = "data-card";

            const header = document.createElement("div");
            header.className = "data-card-header";

            const title = document.createElement("h3");
            title.textContent = course.title;

            const badge = document.createElement("span");
            badge.className = "data-badge";
            badge.textContent = course.code;

            header.appendChild(title);
            header.appendChild(badge);

            const list = document.createElement("ul");
            list.className = "course-detail-list";
            [
                ["Course Code", course.code],
                ["Instructor", course.instructor],
                ["Credits", course.credits],
                ["Schedule", course.schedule],
                ["Location", course.location],
                ["Description", course.description],
                ["Assessment", course.assessment],
                ["Current Grade", course.grade]
            ].forEach(function ([label, value]) {
                const item = document.createElement("li");
                item.innerHTML = "<strong>" + label + ":</strong> " + value;
                list.appendChild(item);
            });

            card.appendChild(header);
            card.appendChild(list);
            courseList.appendChild(card);
        });

        if (courseStatus) {
            courseStatus.textContent = items.length + " course(s) found.";
        }
    }

    function updateCourses() {
        const filtered = courses.filter(function (course) {
            const matchesSearch = course.title.toLowerCase().includes(searchTerm.toLowerCase()) || course.instructor.toLowerCase().includes(searchTerm.toLowerCase()) || course.code.toLowerCase().includes(searchTerm.toLowerCase());
            const matchesCode = codeFilter === "all" || course.code === codeFilter;
            return matchesSearch && matchesCode;
        });

        renderCourses(filtered);
    }

    function populateFilters() {
        if (!courseFilter) return;
        const codes = [...new Set(courses.map(function (course) { return course.code; }))].sort();
        codes.forEach(function (code) {
            const option = document.createElement("option");
            option.value = code;
            option.textContent = code;
            courseFilter.appendChild(option);
        });
    }

    fetch("courses-data.json")
        .then(function (response) {
            if (!response.ok) {
                throw new Error("Failed to load courses");
            }
            return response.json();
        })
        .then(function (data) {
            courses = data.courses || [];
            populateFilters();
            updateCourses();
        })
        .catch(function (error) {
            console.error("Course fetch error:", error);
            courseList.innerHTML = '<p class="data-empty">Unable to load course information right now.</p>';
            if (courseStatus) {
                courseStatus.textContent = "Unable to load courses.";
            }
        });

    if (courseSearch) {
        courseSearch.addEventListener("input", function (event) {
            searchTerm = event.target.value.trim();
            updateCourses();
        });
    }

    if (courseFilter) {
        courseFilter.addEventListener("change", function (event) {
            codeFilter = event.target.value;
            updateCourses();
        });
    }
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
setupAnnouncementList();
setupCourseList();
setupNotifications();
setupModal();
setupSlider();
