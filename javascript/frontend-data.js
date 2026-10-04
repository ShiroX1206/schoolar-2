// This file contains shared functions used by the frontend.
// Phase 3 now uses PHP and MySQL instead of localStorage.

const API_BASE = "/SCHOOlar/api";

async function apiRequest(url, options) {
    const requestOptions = options || {};

    const response = await fetch(API_BASE + url, requestOptions);
    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.error || "The server returned an error.");
    }

    return data;
}

async function getScholarships(includeUnavailable) {
    let url = "/scholarships/list.php";

    if (includeUnavailable === true) {
        url += "?include_unavailable=1";
    }

    return await apiRequest(url);
}

function formatDate(dateText) {
    if (!dateText) {
        return "No deadline";
    }

    const date = new Date(dateText + "T00:00:00");

    return date.toLocaleDateString("en-PH", {
        year: "numeric",
        month: "long",
        day: "numeric"
    });
}

function createScholarshipCard(scholarship) {
    const card = document.createElement("div");
    card.className = "scholarship-card";
    card.setAttribute("data-id", scholarship.id);

    const title = document.createElement("h4");
    title.textContent = scholarship.name;

    const provider = document.createElement("p");
    provider.textContent = scholarship.provider || "";

    const location = document.createElement("p");
    location.textContent = "Location: " + (scholarship.location || scholarship.municipalityName || "Nationwide");

    const deadline = document.createElement("p");
    deadline.textContent = "Deadline: " + formatDate(scholarship.deadline);

    const status = document.createElement("p");
    status.textContent = "Status: " + scholarship.status;

    card.appendChild(title);
    card.appendChild(provider);
    card.appendChild(location);
    card.appendChild(deadline);
    card.appendChild(status);

    card.addEventListener("click", async function () {
        try {
            await apiRequest("/user/interactions.php?action=view", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    scholarship_id: Number(scholarship.id)
                })
            });
        } catch (error) {
            console.log("Could not record scholarship view.", error);
        }

        window.location.href = "/SCHOOlar/User/scholarship-detail.html?id=" + encodeURIComponent(scholarship.id);
    });

    return card;
}
