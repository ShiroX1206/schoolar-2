// This file protects Admin and User pages.
// It also handles the Logout links.

async function checkPageAccess() {
    const path = window.location.pathname;
    let requiredRole = "";

    if (path.indexOf("/Admin/") !== -1) {
        requiredRole = "admin";
    }

    if (path.indexOf("/User/") !== -1) {
        requiredRole = "user";
    }

    if (requiredRole === "") {
        return;
    }

    try {
        const response = await fetch("/SCHOOlar/api/auth/me.php");
        const data = await response.json();

        if (!response.ok || data.role !== requiredRole) {
            if (requiredRole === "admin") {
                window.location.href = "/SCHOOlar/Admin/admin-login.html";
            } else {
                window.location.href = "/SCHOOlar/login.html";
            }
            return;
        }

        document.querySelectorAll(".logout-link").forEach(function (link) {
            link.addEventListener("click", async function (event) {
                event.preventDefault();

                try {
                    await fetch("/SCHOOlar/api/auth/logout.php", {
                        method: "POST"
                    });
                } catch (error) {
                    console.log("Logout request failed.");
                }

                window.location.href = "/SCHOOlar/index.html";
            });
        });
    } catch (error) {
        if (requiredRole === "admin") {
            window.location.href = "/SCHOOlar/Admin/admin-login.html";
        } else {
            window.location.href = "/SCHOOlar/login.html";
        }
    }
}

checkPageAccess();
