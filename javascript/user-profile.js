// This file controls the User Profile page.
// Profile information is now loaded from and saved to MySQL.

const profileForm = document.getElementById("profile-form");
const editButton = document.getElementById("edit-btn");
const saveButton = document.getElementById("save-btn");
const municipalityDropdown = document.getElementById("municipality");
const barangayDropdown = document.getElementById("barangay");
const schoolDropdown = document.getElementById("school");
const courseDropdown = document.getElementById("course");
let currentProfile = null;

function setProfileFieldsDisabled(disabled) {
    const fields = profileForm.querySelectorAll("input, select");

    for (let i = 0; i < fields.length; i++) {
        fields[i].disabled = disabled;
    }

    editButton.disabled = !disabled;
    saveButton.disabled = disabled;
}

async function loadSchools() {
    schoolDropdown.innerHTML = '<option value="">Select School</option>';

    try {
        const schools = await apiRequest("/reference.php?type=schools");

        for (let i = 0; i < schools.length; i++) {
            const option = document.createElement("option");
            option.value = schools[i].id;
            option.textContent = schools[i].name + " (" + (schools[i].acronym || "") + ")";
            schoolDropdown.appendChild(option);
        }
    } catch (error) {
        console.log("Could not load schools from the database.");
    }
}

async function loadCourses(schoolId, selectedCourse) {
    courseDropdown.innerHTML = '<option value="">Select Course</option>';

    if (schoolId === "") {
        courseDropdown.disabled = true;
        return;
    }

    try {
        const courses = await apiRequest("/reference.php?type=courses&school_id=" + encodeURIComponent(schoolId));

        for (let i = 0; i < courses.length; i++) {
            const option = document.createElement("option");
            option.value = courses[i].id;
            option.textContent = (courses[i].code || "") + " - " + courses[i].name;
            courseDropdown.appendChild(option);
        }

        courseDropdown.disabled = false;
        courseDropdown.value = selectedCourse || "";
    } catch (error) {
        console.log("Could not load courses from the database.");
    }
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

async function loadBarangays(cityCode, selectedBarangay) {
    barangayDropdown.innerHTML = '<option value="">Select Barangay</option>';

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
        barangayDropdown.value = selectedBarangay || "";
    } catch (error) {
        console.log("Could not load barangays.");
    }
}

function displayProfile(profile) {
    currentProfile = profile;

    document.getElementById("profile-name").textContent = profile.name;
    document.getElementById("name").value = profile.name || "";
    document.getElementById("age").value = profile.age === null ? "" : profile.age;
    document.getElementById("year").value = profile.year || "";
    document.getElementById("gpa").value = profile.gwa || "";
    document.getElementById("income").value = profile.income || "";

    municipalityDropdown.value = profile.municipality || "";
    loadCourses(profile.school || "", profile.course || "");
    loadBarangays(profile.municipality || "", profile.barangay || "");
}

editButton.addEventListener("click", function () {
    setProfileFieldsDisabled(false);
});

schoolDropdown.addEventListener("change", function () {
    loadCourses(schoolDropdown.value, "");
});

municipalityDropdown.addEventListener("change", function () {
    loadBarangays(municipalityDropdown.value, "");
});

profileForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const profile = {
        name: document.getElementById("name").value.trim(),
        age: document.getElementById("age").value,
        municipality_code: municipalityDropdown.value,
        barangay_code: barangayDropdown.value,
        school_id: schoolDropdown.value,
        course_id: courseDropdown.value,
        year_level: document.getElementById("year").value,
        gwa: document.getElementById("gpa").value,
        annual_family_income: document.getElementById("income").value
    };

    if (profile.name === "") {
        alert("Please enter your name.");
        return;
    }

    try {
        const data = await apiRequest("/user/profile.php", {
            method: "PUT",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(profile)
        });

        displayProfile(data.profile);
        setProfileFieldsDisabled(true);
        alert("Profile saved successfully.");
    } catch (error) {
        alert(error.message);
    }
});

async function startProfilePage() {
    try {
        loadSchools();
        await loadMunicipalities();

        const profile = await apiRequest("/user/profile.php");
        displayProfile(profile);
        setProfileFieldsDisabled(true);
    } catch (error) {
        console.log("Could not load profile:", error.message);
    }
}

startProfilePage();
