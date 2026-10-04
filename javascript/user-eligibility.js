// This file controls the Eligibility Checker.
// The student's profile and scholarship criteria now come from MySQL.

const photoButton = document.getElementById("photo-edit-btn");
const photoInput = document.getElementById("photoInput");
const avatar = document.getElementById("avatar");
const checkButton = document.querySelector(".check-btn");
const resultBox = document.getElementById("resultBox");
const resultValue = document.getElementById("resultValue");
const scholarshipNameElement = document.getElementById("scholarshipName");
let currentScholarship = null;
let currentProfile = null;

async function loadCheckerData() {
    try {
        const profile = await apiRequest("/user/profile.php");
        currentProfile = profile;

        const id = new URLSearchParams(window.location.search).get("id");

        if (id !== null) {
            currentScholarship = await apiRequest("/scholarships/list.php?id=" + encodeURIComponent(id));
        } else {
            const scholarships = await getScholarships(false);
            currentScholarship = scholarships.length > 0 ? scholarships[0] : null;
        }

        document.getElementById("age").value = profile.age === null ? "" : profile.age;
        document.getElementById("school").value = profile.school_name || "";
        document.getElementById("course").value = profile.course_name || "";
        document.getElementById("yearLevel").value = profile.year || "";
        document.getElementById("gpa").value = profile.gwa || "";
        document.getElementById("income").value = profile.income || "";

        if (currentScholarship !== null) {
            scholarshipNameElement.textContent = currentScholarship.name;
        }
    } catch (error) {
        scholarshipNameElement.textContent = "Could not load scholarship.";
    }
}

// This function checks if the student's course matches at least one
// of the scholarship's allowed course keywords (for example "Engineering"
// matches a course named "Bachelor of Science in Civil Engineering").
function courseMatchesScope(courseName, courseScope) {
    if (courseScope.length === 0) {
        return true;
    }

    if (!courseName) {
        return false;
    }

    const lowerCourseName = courseName.toLowerCase();

    for (let i = 0; i < courseScope.length; i++) {
        if (lowerCourseName.indexOf(courseScope[i].toLowerCase()) !== -1) {
            return true;
        }
    }

    return false;
}

// This function checks if the student's saved address matches the
// scholarship's location. If the scholarship has a barangay set, the
// student's barangay must match. Otherwise, if it has a municipality
// set, the student's municipality must match.
function studentMeetsResidency(profile, scholarship) {
    if (scholarship.barangay_code) {
        return profile.barangay === scholarship.barangay_code;
    }

    if (scholarship.municipality_code) {
        return profile.municipality === scholarship.municipality_code;
    }

    // The scholarship has no specific location, so there is nothing
    // to check the student's residency against.
    return true;
}

function checkEligibility() {
    if (currentScholarship === null) {
        resultValue.textContent = "Scholarship not found.";
        resultBox.classList.add("show");
        return;
    }

    const age = Number(document.getElementById("age").value);
    const gwa = Number(document.getElementById("gpa").value);
    const income = Number(document.getElementById("income").value);
    const yearLevel = document.getElementById("yearLevel").value;
    const criteria = currentScholarship.criteria;
    const reasons = [];

    if (criteria.min_age !== null && age < Number(criteria.min_age)) {
        reasons.push("Your age is below the minimum age.");
    }

    if (criteria.max_age !== null && age > Number(criteria.max_age)) {
        reasons.push("Your age is above the maximum age.");
    }

    if (criteria.min_gwa !== null && gwa < Number(criteria.min_gwa)) {
        reasons.push("Your GWA / average does not meet the minimum requirement.");
    }

    if (criteria.max_gwa !== null && gwa > Number(criteria.max_gwa)) {
        reasons.push("Your GWA / average is above the allowed maximum.");
    }

    if (criteria.max_annual_income !== null && income > Number(criteria.max_annual_income)) {
        reasons.push("Your annual family income is above the allowed amount.");
    }

    if (criteria.year_levels.length > 0 && criteria.year_levels.indexOf(yearLevel) === -1) {
        reasons.push("Your year level is not included in the scholarship requirements.");
    }

    if (!courseMatchesScope(currentProfile.course_name, criteria.course_scope)) {
        reasons.push("Your course is not included in the scholarship requirements.");
    }

    if (criteria.residency_required && !studentMeetsResidency(currentProfile, currentScholarship)) {
        reasons.push("This scholarship requires residency in a specific area that does not match your saved address.");
    }

    resultBox.classList.add("show");

    if (reasons.length === 0) {
        resultValue.textContent = "You appear eligible based on the entered information.";
    } else {
        resultValue.textContent = "Not eligible: " + reasons.join(" ");
    }
}

if (photoButton !== null && photoInput !== null) {
    photoButton.addEventListener("click", function () {
        photoInput.click();
    });

    photoInput.addEventListener("change", function () {
        if (photoInput.files.length === 0) {
            return;
        }

        const selectedFile = photoInput.files[0];
        const reader = new FileReader();

        reader.onload = function (event) {
            avatar.style.backgroundImage = "url('" + event.target.result + "')";
            avatar.style.backgroundSize = "cover";
            avatar.style.backgroundPosition = "center";
        };

        reader.readAsDataURL(selectedFile);
    });
}

checkButton.addEventListener("click", function () {
    checkEligibility();
});

loadCheckerData();
