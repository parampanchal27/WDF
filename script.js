const form = document.getElementById("registerForm");
const successMessage = document.getElementById("successMessage");

function setError(id, message) {
    const field = document.getElementById(id);
    const error = document.getElementById(id + "Error");

    field.style.border = "2px solid red";
    error.textContent = message;
}

function clearError(id) {
    const field = document.getElementById(id);
    const error = document.getElementById(id + "Error");

    field.style.border = "1px solid #ccc";
    error.textContent = "";
}

form.addEventListener("submit", function (event) {
    event.preventDefault();

    const username = document.getElementById("username").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirmPassword").value;

    let isValid = true;

    if (username === "") {
        setError("username", "Username is required.");
        isValid = false;
    } else if (username.length < 3) {
        setError("username", "Username must be at least 3 characters.");
        isValid = false;
    } else {
        clearError("username");
    }

    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email === "") {
        setError("email", "Email is required.");
        isValid = false;
    } else if (!emailPattern.test(email)) {
        setError("email", "Please enter a valid email.");
        isValid = false;
    } else {
        clearError("email");
    }

    if (password === "") {
        setError("password", "Password is required.");
        isValid = false;
    } else if (password.length < 6) {
        setError("password", "Password must be at least 6 characters.");
        isValid = false;
    } else {
        clearError("password");
    }

    if (confirmPassword === "") {
        setError("confirmPassword", "Please confirm your password.");
        isValid = false;
    } else if (password !== confirmPassword) {
        setError("confirmPassword", "Passwords do not match.");
        isValid = false;
    } else {
        clearError("confirmPassword");
    }

    if (isValid) {
        successMessage.textContent = "Registration successful!";
        successMessage.style.color = "green";
        form.reset();
    } else {
        successMessage.textContent = "";
    }
});