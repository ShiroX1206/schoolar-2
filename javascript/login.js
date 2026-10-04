// This file controls the User Login page.

const loginForm = document.getElementById("login-form");
const errorMessage = document.getElementById("error-msg");

loginForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;

    errorMessage.textContent = "";
    errorMessage.classList.remove("show");
    errorMessage.style.color = "#dc2626";

    if (email === "") {
        errorMessage.textContent = "Please enter your email address.";
        errorMessage.classList.add("show");
        return;
    }

    if (!email.includes("@") || !email.includes(".")) {
        errorMessage.textContent = "Please enter a valid email address.";
        errorMessage.classList.add("show");
        return;
    }

    if (password === "") {
        errorMessage.textContent = "Please enter your password.";
        errorMessage.classList.add("show");
        return;
    }

    try {
        const response = await fetch("/SCHOOlar/api/auth/login.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                email: email,
                password: password,
                role: "user"
            })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || "Login failed.");
        }

        window.location.href = "/SCHOOlar/User/user-dashboard.html";
    } catch (error) {
        errorMessage.textContent = error.message;
        errorMessage.classList.add("show");
    }
});

const forgotPasswordLink = document.getElementById("forgot-password");

if (forgotPasswordLink !== null) {
    forgotPasswordLink.addEventListener("click", function (event) {
        event.preventDefault();
        alert("Password recovery is not included in this phase.");
    });
}
