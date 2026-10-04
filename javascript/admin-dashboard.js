// This file controls the Admin Dashboard.

const totalElement = document.getElementById("stat-total");
const activeElement = document.getElementById("stat-active");
const inactiveElement = document.getElementById("stat-inactive");
const expiringElement = document.getElementById("stat-expiring");
const recentList = document.getElementById("recent-list");

async function loadAdminName() {
    try {
        const response = await fetch("/SCHOOlar/api/auth/me.php");
        const data = await response.json();
        const nameElement = document.getElementById("name");

        if (response.ok && nameElement) {
            nameElement.textContent = data.full_name;
        }
    } catch (error) {
        console.log("Could not load admin name.");
    }
}

async function loadDashboard() {
    try {
        const scholarships = await getScholarships(true);

        totalElement.textContent = scholarships.length;

        let active = 0;
        let inactive = 0;
        let expiring = 0;
        const today = new Date();

        for (let i = 0; i < scholarships.length; i++) {
            if (scholarships[i].status === "available") {
                active++;
            } else {
                inactive++;
            }

            if (scholarships[i].deadline) {
                const deadline = new Date(scholarships[i].deadline + "T23:59:59");
                const difference = deadline.getTime() - today.getTime();
                const days = difference / (1000 * 60 * 60 * 24);

                if (days >= 0 && days <= 30) {
                    expiring++;
                }
            }
        }

        activeElement.textContent = active;
        inactiveElement.textContent = inactive;
        expiringElement.textContent = expiring;

        displayRecent(scholarships);
    } catch (error) {
        console.log("Error loading dashboard:", error.message);
        recentList.textContent = "Could not load scholarship data.";
    }
}

function displayRecent(scholarships) {
    recentList.innerHTML = "";

    if (scholarships.length === 0) {
        recentList.textContent = "No scholarships found.";
        return;
    }

    const limit = Math.min(5, scholarships.length);

    for (let i = 0; i < limit; i++) {
        const row = document.createElement("div");
        row.className = "recent-item";

        const name = document.createElement("span");
        name.className = "recent-name";
        name.textContent = scholarships[i].name;

        const status = document.createElement("span");
        status.className = scholarships[i].status === "available" ? "status-pill active" : "status-pill inactive";
        status.textContent = scholarships[i].status;

        row.appendChild(name);
        row.appendChild(status);
        recentList.appendChild(row);
    }
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

async function startDashboard() {
    await loadAdminName();
    loadDashboard();
}

startDashboard();
