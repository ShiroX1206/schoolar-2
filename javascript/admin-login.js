// This file controls the Admin Login page.

const adminLoginForm = document.getElementById("admin-login-form");
const adminErrorMessage = document.getElementById("error-msg");

adminLoginForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const email = document.getElementById("admin-email").value.trim();
    const password = document.getElementById("admin-password").value;

    adminErrorMessage.textContent = "";
    adminErrorMessage.classList.remove("show");
    adminErrorMessage.style.color = "#dc2626";

    if (email === "") {
        adminErrorMessage.textContent = "Please enter the admin email address.";
        adminErrorMessage.classList.add("show");
        return;
    }

    if (!email.includes("@") || !email.includes(".")) {
        adminErrorMessage.textContent = "Please enter a valid email address.";
        adminErrorMessage.classList.add("show");
        return;
    }

    if (password === "") {
        adminErrorMessage.textContent = "Please enter the admin password.";
        adminErrorMessage.classList.add("show");
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
                role: "admin"
            })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || "Login failed.");
        }

        window.location.href = "/SCHOOlar/Admin/admin-dashboard.html";
    } catch (error) {
        adminErrorMessage.textContent = error.message;
        adminErrorMessage.classList.add("show");
    }
});
