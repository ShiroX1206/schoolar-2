// This file controls the Manage Scholarships page.
// Scholarship records are now stored in MySQL through PHP.

const addScholarshipButton = document.getElementById("add-btn");
const cancelButton = document.getElementById("cancel-btn");
const modalOverlay = document.getElementById("modal-overlay");
const scholarshipForm = document.getElementById("scholarship-form");
const scholarshipList = document.getElementById("scholarship-list");
const searchInput = document.getElementById("search-input");
const statusFilter = document.getElementById("status-filter");
const municipalityDropdown = document.getElementById("field-municipality");
const barangayDropdown = document.getElementById("field-barangay");

let editingScholarshipId = "";
let allScholarships = [];

function openModal() {
    modalOverlay.classList.add("open");
}

function closeModal() {
    modalOverlay.classList.remove("open");
    scholarshipForm.reset();
    editingScholarshipId = "";
    document.getElementById("modal-title").textContent = "Add Scholarship";
    barangayDropdown.innerHTML = '<option value="">Any barangay</option>';
    barangayDropdown.disabled = true;
}

function splitText(text) {
    if (!text) {
        return [];
    }

    const lines = text.split(/\n|,/);
    const result = [];

    for (let i = 0; i < lines.length; i++) {
        const value = lines[i].trim();
        if (value !== "") {
            result.push(value);
        }
    }

    return result;
}

function getCheckedYearLevels() {
    const checkboxes = document.querySelectorAll("#year-level-checkboxes input[type='checkbox']");
    const levels = [];

    for (let i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].checked) {
            levels.push(checkboxes[i].value);
        }
    }

    return levels;
}

function setCheckedYearLevels(levels) {
    const checkboxes = document.querySelectorAll("#year-level-checkboxes input[type='checkbox']");

    for (let i = 0; i < checkboxes.length; i++) {
        checkboxes[i].checked = levels.indexOf(checkboxes[i].value) !== -1;
    }
}

function displayScholarships() {
    const searchText = searchInput.value.toLowerCase().trim();
    const selectedStatus = statusFilter.value;

    scholarshipList.innerHTML = "";
    let shownCount = 0;

    for (let i = 0; i < allScholarships.length; i++) {
        const scholarship = allScholarships[i];
        const nameMatches = scholarship.name.toLowerCase().indexOf(searchText) !== -1;
        const statusMatches = selectedStatus === "all" || scholarship.status === selectedStatus;

        if (!nameMatches || !statusMatches) {
            continue;
        }

        const row = document.createElement("div");
        row.className = "scholarship-row";

        const information = document.createElement("div");
        information.className = "scholarship-info";

        const title = document.createElement("h4");
        title.textContent = scholarship.name;

        const provider = document.createElement("p");
        provider.textContent = scholarship.provider || "";

        const deadline = document.createElement("p");
        deadline.textContent = "Deadline: " + formatDate(scholarship.deadline);

        information.appendChild(title);
        information.appendChild(provider);
        information.appendChild(deadline);

        const actions = document.createElement("div");
        actions.className = "scholarship-actions";

        const statusButton = document.createElement("button");
        statusButton.type = "button";

        if (scholarship.status === "available") {
            statusButton.textContent = "Available";
            statusButton.className = "status available";
        } else {
            statusButton.textContent = "Not Available";
            statusButton.className = "status inactive";
        }
        statusButton.addEventListener("click", function () {
            changeStatus(scholarship.id, scholarship.status);
        });

        const editButton = document.createElement("button");
        editButton.type = "button";
        editButton.textContent = "Edit";
        editButton.addEventListener("click", function () {
            editScholarship(scholarship.id);
        });

        const deleteButton = document.createElement("button");
        deleteButton.type = "button";
        deleteButton.textContent = "Delete";
        deleteButton.addEventListener("click", function () {
            deleteScholarship(scholarship.id);
        });

        actions.appendChild(statusButton);
        actions.appendChild(editButton);
        actions.appendChild(deleteButton);
        row.appendChild(information);
        row.appendChild(actions);
        scholarshipList.appendChild(row);
        shownCount++;
    }

    if (shownCount === 0) {
        scholarshipList.textContent = "No scholarships match your search.";
    }
}

async function loadScholarships() {
    try {
        allScholarships = await getScholarships(true);
        displayScholarships();
    } catch (error) {
        scholarshipList.textContent = "Could not load scholarships: " + error.message;
    }
}

async function changeStatus(id, oldStatus) {
    let scholarship = null;

    for (let i = 0; i < allScholarships.length; i++) {
        if (Number(allScholarships[i].id) === Number(id)) {
            scholarship = allScholarships[i];
            break;
        }
    }

    if (scholarship === null) {
        return;
    }

    const newStatus = oldStatus === "available" ? "not available" : "available";

    try {
        const response = await fetch("/SCHOOlar/api/admin/scholarships.php?id=" + encodeURIComponent(id), {
            method: "PUT",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                name: scholarship.name,
                provider_name: scholarship.provider,
                benefits: scholarship.benefits,
                requirements: scholarship.requirements,
                deadline: scholarship.deadline,
                status: newStatus,
                municipality_code: scholarship.municipality_code,
                barangay_code: scholarship.barangay_code,
                municipality_name: scholarship.municipality_name,
                barangay_name: scholarship.barangay_name,
                contact_email: scholarship.contactEmail,
                contact_phone: scholarship.contactPhone,
                criteria: scholarship.criteria
            })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || "Could not change status.");
        }

        await loadScholarships();
    } catch (error) {
        alert(error.message);
    }
}

async function deleteScholarship(id) {
    if (!confirm("Are you sure you want to delete this scholarship?")) {
        return;
    }

    try {
        const response = await fetch("/SCHOOlar/api/admin/scholarships.php?id=" + encodeURIComponent(id), {
            method: "DELETE"
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || "Could not delete scholarship.");
        }

        await loadScholarships();
    } catch (error) {
        alert(error.message);
    }
}

function editScholarship(id) {
    let scholarship = null;

    for (let i = 0; i < allScholarships.length; i++) {
        if (Number(allScholarships[i].id) === Number(id)) {
            scholarship = allScholarships[i];
            break;
        }
    }

    if (scholarship === null) {
        return;
    }

    editingScholarshipId = id;
    document.getElementById("modal-title").textContent = "Edit Scholarship";
    document.getElementById("record-id").value = id;
    document.getElementById("field-name").value = scholarship.name;
    document.getElementById("field-provider").value = scholarship.provider || "";
    document.getElementById("field-deadline").value = scholarship.deadline || "";
    document.getElementById("field-contact-email").value = scholarship.contactEmail || "";
    document.getElementById("field-contact-phone").value = scholarship.contactPhone || "";
    document.getElementById("field-benefits").value = scholarship.benefits.join("\n");
    document.getElementById("field-requirements").value = scholarship.requirements.join("\n");
    document.getElementById("field-status").value = scholarship.status;
    document.getElementById("field-min-gwa").value = scholarship.minGwa === null ? "" : scholarship.minGwa;
    document.getElementById("field-max-gwa").value = scholarship.maxGwa === null ? "" : scholarship.maxGwa;
    document.getElementById("field-max-income").value = scholarship.maxIncome === null ? "" : scholarship.maxIncome;
    document.getElementById("field-min-age").value = scholarship.minAge === null ? "" : scholarship.minAge;
    document.getElementById("field-max-age").value = scholarship.maxAge === null ? "" : scholarship.maxAge;
    document.getElementById("field-course-scope").value = scholarship.courseScope || "";
    document.getElementById("field-residency").checked = scholarship.residency === true;
    setCheckedYearLevels(scholarship.yearLevels || []);

    municipalityDropdown.value = scholarship.municipality_code || "";

    if (scholarship.municipality_code) {
        loadBarangays(scholarship.municipality_code).then(function () {
            barangayDropdown.value = scholarship.barangay_code || "";
        });
    } else {
        barangayDropdown.innerHTML = '<option value="">Any barangay</option>';
        barangayDropdown.disabled = true;
    }

    openModal();
}

async function loadMunicipalities() {
    try {
        const response = await fetch("https://psgc.cloud/api/cities-municipalities");
        const cityList = await response.json();

        cityList.sort(function (cityA, cityB) {
            return cityA.name.localeCompare(cityB.name);
        });

        for (let i = 0; i < cityList.length; i++) {
            const option = document.createElement("option");
            option.value = cityList[i].code;
            option.textContent = cityList[i].name;
            municipalityDropdown.appendChild(option);
        }
    } catch (error) {
        console.log("Could not load municipalities.");
    }
}

async function loadBarangays(cityCode) {
    barangayDropdown.innerHTML = '<option value="">Any barangay</option>';

    if (cityCode === "") {
        barangayDropdown.disabled = true;
        return;
    }

    try {
        const url = "https://psgc.cloud/api/cities-municipalities/" + cityCode + "/barangays";
        const response = await fetch(url);
        const barangayList = await response.json();

        barangayList.sort(function (barangayA, barangayB) {
            return barangayA.name.localeCompare(barangayB.name);
        });

        for (let i = 0; i < barangayList.length; i++) {
            const option = document.createElement("option");
            option.value = barangayList[i].code;
            option.textContent = barangayList[i].name;
            barangayDropdown.appendChild(option);
        }

        barangayDropdown.disabled = false;
    } catch (error) {
        console.log("Could not load barangays.");
    }
}

addScholarshipButton.addEventListener("click", function () {
    editingScholarshipId = "";
    scholarshipForm.reset();
    document.getElementById("modal-title").textContent = "Add Scholarship";
    barangayDropdown.innerHTML = '<option value="">Any barangay</option>';
    barangayDropdown.disabled = true;
    openModal();
});

cancelButton.addEventListener("click", function () {
    closeModal();
});

modalOverlay.addEventListener("click", function (event) {
    if (event.target === modalOverlay) {
        closeModal();
    }
});

municipalityDropdown.addEventListener("change", function () {
    loadBarangays(municipalityDropdown.value);
});

searchInput.addEventListener("input", function () {
    displayScholarships();
});

statusFilter.addEventListener("change", function () {
    displayScholarships();
});

scholarshipForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const name = document.getElementById("field-name").value.trim();
    const provider = document.getElementById("field-provider").value.trim();
    const contactEmail = document.getElementById("field-contact-email").value.trim();

    if (name === "") {
        alert("Please enter the scholarship name.");
        return;
    }

    if (contactEmail !== "" && (!contactEmail.includes("@") || !contactEmail.includes("."))) {
        alert("Please enter a valid contact email.");
        return;
    }

    const selectedMunicipality = municipalityDropdown.value;
    const selectedBarangay = barangayDropdown.value;
    const municipalityOption = municipalityDropdown.options[municipalityDropdown.selectedIndex];
    const barangayOption = barangayDropdown.options[barangayDropdown.selectedIndex];

    const scholarshipData = {
        name: name,
        provider_name: provider,
        municipality_code: selectedMunicipality,
        barangay_code: selectedBarangay,
        municipality_name: selectedMunicipality === "" ? "Nationwide" : municipalityOption.textContent,
        barangay_name: selectedBarangay === "" ? "Any Barangay" : barangayOption.textContent,
        deadline: document.getElementById("field-deadline").value,
        contact_email: contactEmail,
        contact_phone: document.getElementById("field-contact-phone").value.trim(),
        benefits: splitText(document.getElementById("field-benefits").value),
        requirements: splitText(document.getElementById("field-requirements").value),
        status: document.getElementById("field-status").value,
        criteria: {
            min_gwa: document.getElementById("field-min-gwa").value,
            max_gwa: document.getElementById("field-max-gwa").value,
            max_annual_income: document.getElementById("field-max-income").value,
            min_age: document.getElementById("field-min-age").value,
            max_age: document.getElementById("field-max-age").value,
            year_levels: getCheckedYearLevels(),
            course_scope: splitText(document.getElementById("field-course-scope").value),
            residency_required: document.getElementById("field-residency").checked
        }
    };

    let url = "/SCHOOlar/api/admin/scholarships.php";
    let method = "POST";

    if (editingScholarshipId !== "") {
        url += "?id=" + encodeURIComponent(editingScholarshipId);
        method = "PUT";
    }

    try {
        const response = await fetch(url, {
            method: method,
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(scholarshipData)
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || "Could not save scholarship.");
        }

        closeModal();
        await loadScholarships();
    } catch (error) {
        alert(error.message);
    }
});

async function startAdminScholarships() {
    try {
        await loadMunicipalities();
        await loadScholarships();
    } catch (error) {
        scholarshipList.textContent = "Could not connect to the server.";
    }
}

startAdminScholarships();
