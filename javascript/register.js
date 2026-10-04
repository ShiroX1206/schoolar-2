// This file controls the Register page.

const municipalityDropdown = document.getElementById("municipality");
const barangayDropdown = document.getElementById("barangay");
const schoolDropdown = document.getElementById("school");
const courseDropdown = document.getElementById("course");
const registerForm = document.getElementById("register-form");
const registerErrorMessage = document.getElementById("error-msg");

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
    } catch (error) {
        console.log("Could not load barangays.");
    }
}

async function loadSchools() {
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

async function loadCourses(schoolId) {
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
            option.textContent = courses[i].name + " (" + (courses[i].code || "") + ")";
            courseDropdown.appendChild(option);
        }

        courseDropdown.disabled = false;
    } catch (error) {
        console.log("Could not load courses from the database.");
    }
}

municipalityDropdown.addEventListener("change", function () {
    loadBarangays(municipalityDropdown.value);
});

schoolDropdown.addEventListener("change", function () {
    loadCourses(schoolDropdown.value);
});

registerForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    registerErrorMessage.textContent = "";
    registerErrorMessage.classList.remove("show");
    registerErrorMessage.style.color = "#dc2626";

    const name = document.getElementById("name").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;
    const birthDate = document.getElementById("birth-date").value;
    const gwa = document.getElementById("gwa").value;
    const income = document.getElementById("family-income").value;
    const yearLevel = document.getElementById("year-level").value;

    if (name === "") {
        registerErrorMessage.textContent = "Please enter your full name.";
        registerErrorMessage.classList.add("show");
        return;
    }

    if (!email.includes("@") || !email.includes(".")) {
        registerErrorMessage.textContent = "Please enter a valid email address.";
        registerErrorMessage.classList.add("show");
        return;
    }

    if (password.length < 8) {
        registerErrorMessage.textContent = "Password must be at least 8 characters.";
        registerErrorMessage.classList.add("show");
        return;
    }

    if (municipalityDropdown.value === "" || barangayDropdown.value === "") {
        registerErrorMessage.textContent = "Please select your location.";
        registerErrorMessage.classList.add("show");
        return;
    }

    if (birthDate === "" || schoolDropdown.value === "" || courseDropdown.value === "" || yearLevel === "") {
        registerErrorMessage.textContent = "Please complete your student details.";
        registerErrorMessage.classList.add("show");
        return;
    }

    if (gwa === "" || Number(gwa) < 1 || Number(gwa) > 5) {
        registerErrorMessage.textContent = "GWA must be between 1.00 and 5.00.";
        registerErrorMessage.classList.add("show");
        return;
    }

    if (income === "" || Number(income) < 0) {
        registerErrorMessage.textContent = "Please enter a valid annual family income.";
        registerErrorMessage.classList.add("show");
        return;
    }

    try {
        const response = await fetch("/SCHOOlar/api/auth/register.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                name: name,
                email: email,
                password: password,
                municipality_code: municipalityDropdown.value,
                barangay_code: barangayDropdown.value,
                birth_date: birthDate,
                school_id: schoolDropdown.value,
                course_id: courseDropdown.value,
                year_level: yearLevel,
                gwa: gwa,
                annual_family_income: income
            })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.error || "Registration failed.");
        }

        window.location.href = "/SCHOOlar/User/user-dashboard.html";
    } catch (error) {
        registerErrorMessage.textContent = error.message;
        registerErrorMessage.classList.add("show");
    }
});

loadMunicipalities();
loadSchools();
