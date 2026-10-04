// Get the dashboard elements
const totalElement = document.getElementById("stat-total");
const activeElement = document.getElementById("stat-active");
const inactiveElement = document.getElementById("stat-inactive");
const expiringElement = document.getElementById("stat-expiring");
const recentList = document.getElementById("recent-list");

// Load admin information
async function loadAdmin() {
    try {
        const response = await fetch(
            "/SCHOOlar/api/auth/me.php"
        );

        const data = await response.json();

        if (!response.ok || data.role !== "admin") {
            window.location.href =
                "/SCHOOlar/Admin/admin-login.html";
            return;
        }

        const nameElement =
            document.getElementById("name");

        if (nameElement) {
            nameElement.textContent = data.full_name;
        }

    } catch (error) {
        console.log("Error loading admin:", error);
    }
}

// Load scholarship statistics
async function loadDashboard() {
    try {
        // Admin API returns all scholarships
        const response = await fetch(
            "/SCHOOlar/api/admin/scholarships.php"
        );

        const scholarships = await response.json();

        if (!response.ok) {
            throw new Error(
                scholarships.error ||
                "Could not load scholarships."
            );
        }

        // Count total scholarships
        totalElement.textContent = scholarships.length;

        // Count available scholarships
        const active = scholarships.filter(function (item) {
            return item.status === "available";
        });

        activeElement.textContent = active.length;

        // Count unavailable scholarships
        const inactive = scholarships.filter(function (item) {
            return item.status === "not available";
        });

        inactiveElement.textContent = inactive.length;

        // Count scholarships with a deadline in the next 30 days
        let expiring = 0;

        const today = new Date();

        scholarships.forEach(function (item) {
            if (!item.deadline) {
                return;
            }

            const deadline = new Date(item.deadline);

            const difference =
                deadline.getTime() - today.getTime();

            const days =
                difference / (1000 * 60 * 60 * 24);

            if (days >= 0 && days <= 30) {
                expiring++;
            }
        });

        expiringElement.textContent = expiring;

        // Show recent scholarships
        displayRecent(scholarships);

    } catch (error) {
        console.log("Error loading dashboard:", error);
    }
}

// Show recently updated scholarships
function displayRecent(scholarships) {
    if (!recentList) {
        return;
    }

    recentList.innerHTML = "";

    scholarships.slice(0, 5).forEach(function (item) {
        const row = document.createElement("div");

        row.className = "recent-item";

        row.innerHTML =
            "<span>" + item.name + "</span>" +
            "<span>" +
            (item.status || "") +
            "</span>";

        recentList.appendChild(row);
    });
}

// Logout
document.querySelectorAll(".logout-link").forEach(function (link) {
    link.addEventListener("click", async function (event) {
        event.preventDefault();

        await fetch("/SCHOOlar/api/auth/logout.php", {
            method: "POST"
        });

        window.location.href = "/SCHOOlar/index.html";
    });
});

// Start
loadAdmin();
loadDashboard();
