// This file controls Saved Scholarships and Recently Viewed.

const savedContent = document.getElementById("saved-content");
const recentContent = document.getElementById("recent-content");
const tabButtons = document.querySelectorAll(".buttons button");

async function displaySavedScholarships() {
    savedContent.innerHTML = "";

    try {
        const scholarships = await apiRequest("/user/interactions.php?action=saved");

        if (scholarships.length === 0) {
            savedContent.textContent = "You have no saved scholarships yet.";
            return;
        }

        for (let i = 0; i < scholarships.length; i++) {
            savedContent.appendChild(createScholarshipCard(scholarships[i]));
        }
    } catch (error) {
        savedContent.textContent = "Could not load saved scholarships.";
    }
}

async function displayRecentScholarships() {
    recentContent.innerHTML = "";

    try {
        const scholarships = await apiRequest("/user/interactions.php?action=viewed");

        if (scholarships.length === 0) {
            recentContent.textContent = "You have not viewed any scholarships yet.";
            return;
        }

        for (let i = 0; i < scholarships.length; i++) {
            recentContent.appendChild(createScholarshipCard(scholarships[i]));
        }
    } catch (error) {
        recentContent.textContent = "Could not load recently viewed scholarships.";
    }
}

for (let i = 0; i < tabButtons.length; i++) {
    tabButtons[i].addEventListener("click", async function () {
        for (let j = 0; j < tabButtons.length; j++) {
            tabButtons[j].classList.remove("active");
        }

        this.classList.add("active");

        if (this.getAttribute("data-tab") === "saved") {
            savedContent.style.display = "block";
            recentContent.style.display = "none";
            await displaySavedScholarships();
        } else {
            savedContent.style.display = "none";
            recentContent.style.display = "block";
            await displayRecentScholarships();
        }
    });
}

async function startSavedPage() {
    await displaySavedScholarships();
    await displayRecentScholarships();
}

startSavedPage();
