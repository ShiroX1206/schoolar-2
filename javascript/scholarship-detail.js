// This file controls the Scholarship Details page.

const scholarshipName = document.getElementById("scholarshipName");
const provider = document.getElementById("provider");
const category = document.getElementById("category");
const benefitsList = document.getElementById("benefitsList");
const requirementsList = document.getElementById("requirementsList");
const criteriaList = document.getElementById("criteriaList");
const deadlineValue = document.getElementById("deadlineValue");
const locationValue = document.getElementById("locationValue");
const contactList = document.getElementById("contactList");
const saveButton = document.getElementById("saveBtn");
const eligibilityButton = document.querySelector(".eligibility-btn");

function getScholarshipIdFromUrl() {
    const parameters = new URLSearchParams(window.location.search);
    return parameters.get("id");
}

async function getCurrentScholarship() {
    const id = getScholarshipIdFromUrl();

    if (id === null) {
        const scholarships = await getScholarships(false);
        return scholarships.length > 0 ? scholarships[0] : null;
    }

    try {
        return await apiRequest("/scholarships/list.php?id=" + encodeURIComponent(id));
    } catch (error) {
        return null;
    }
}

function fillList(listElement, values) {
    listElement.innerHTML = "";

    for (let i = 0; i < values.length; i++) {
        const item = document.createElement("li");
        item.textContent = values[i];
        listElement.appendChild(item);
    }
}

async function displayScholarship() {
    const scholarship = await getCurrentScholarship();

    if (scholarship === null) {
        scholarshipName.textContent = "Scholarship not found";
        provider.textContent = "";
        return;
    }

    scholarshipName.textContent = scholarship.name;
    provider.textContent = scholarship.provider || "";
    category.textContent = scholarship.category || "Scholarship";
    deadlineValue.textContent = formatDate(scholarship.deadline);
    locationValue.textContent = scholarship.location || scholarship.municipalityName || "Nationwide";

    fillList(benefitsList, scholarship.benefits || []);
    fillList(requirementsList, scholarship.requirements || []);

    const criteria = [];

    if (scholarship.minGwa !== null && scholarship.minGwa !== "") {
        criteria.push("Minimum GWA / Average: " + scholarship.minGwa);
    }

    if (scholarship.maxGwa !== null && scholarship.maxGwa !== "") {
        criteria.push("Maximum GWA / Average: " + scholarship.maxGwa);
    }

    if (scholarship.maxIncome !== null && scholarship.maxIncome !== "") {
        criteria.push("Maximum family income: ₱" + scholarship.maxIncome);
    }

    if (scholarship.minAge !== null && scholarship.minAge !== "") {
        criteria.push("Minimum age: " + scholarship.minAge);
    }

    if (scholarship.maxAge !== null && scholarship.maxAge !== "") {
        criteria.push("Maximum age: " + scholarship.maxAge);
    }

    if (scholarship.yearLevels && scholarship.yearLevels.length > 0) {
        criteria.push("Year levels: " + scholarship.yearLevels.join(", "));
    }

    if (scholarship.courseScope !== "") {
        criteria.push("Course scope: " + scholarship.courseScope);
    }

    if (scholarship.residency) {
        criteria.push("Residency requirement applies");
    }

    fillList(criteriaList, criteria);

    contactList.innerHTML = "";

    if (scholarship.contactEmail !== "") {
        const email = document.createElement("li");
        email.textContent = "Email: " + scholarship.contactEmail;
        contactList.appendChild(email);
    }

    if (scholarship.contactPhone !== "") {
        const phone = document.createElement("li");
        phone.textContent = "Phone: " + scholarship.contactPhone;
        contactList.appendChild(phone);
    }

    await updateSaveButton(scholarship.id);
}

async function updateSaveButton(id) {
    try {
        const data = await apiRequest("/user/interactions.php?action=status&scholarship_id=" + encodeURIComponent(id));

        if (data.saved) {
            saveButton.classList.add("saved");
            saveButton.setAttribute("aria-label", "Remove from saved scholarships");
        } else {
            saveButton.classList.remove("saved");
            saveButton.setAttribute("aria-label", "Save scholarship");
        }
    } catch (error) {
        saveButton.classList.remove("saved");
    }
}

saveButton.addEventListener("click", async function () {
    const scholarship = await getCurrentScholarship();

    if (scholarship === null) {
        return;
    }

    try {
        const currentStatus = await apiRequest("/user/interactions.php?action=status&scholarship_id=" + encodeURIComponent(scholarship.id));
        let method = "POST";
        let url = "/user/interactions.php?action=saved";

        if (currentStatus.saved) {
            method = "DELETE";
            url += "&scholarship_id=" + encodeURIComponent(scholarship.id);
        }

        const options = {
            method: method
        };

        if (method === "POST") {
            options.headers = {
                "Content-Type": "application/json"
            };
            options.body = JSON.stringify({
                scholarship_id: Number(scholarship.id)
            });
        }

        await apiRequest(url, options);
        await updateSaveButton(scholarship.id);
    } catch (error) {
        alert(error.message);
    }
});

eligibilityButton.addEventListener("click", async function () {
    const scholarship = await getCurrentScholarship();

    if (scholarship === null) {
        return;
    }

    window.location.href = "/SCHOOlar/User/user-eligibility.html?id=" + encodeURIComponent(scholarship.id);
});

displayScholarship();
