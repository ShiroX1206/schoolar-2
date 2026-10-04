// This file controls the Find Scholarship page.

const provinceDropdown = document.getElementById("province");
const locationDropdown = document.getElementById("location");
const searchForm = document.getElementById("search-form");
const searchInput = document.getElementById("search");
const resultsContainer = document.getElementById("scholarship-results");

async function loadProvinces() {
    try {
        const response = await fetch("https://psgc.cloud/api/provinces");
        const provinceList = await response.json();

        provinceList.sort(function (provinceA, provinceB) {
            return provinceA.name.localeCompare(provinceB.name);
        });

        for (let i = 0; i < provinceList.length; i++) {
            const option = document.createElement("option");
            option.value = provinceList[i].code;
            option.textContent = provinceList[i].name;
            provinceDropdown.appendChild(option);
        }
    } catch (error) {
        console.log("Could not load provinces.");
    }
}

async function loadCitiesForProvince(provinceCode) {
    locationDropdown.innerHTML = '<option value="">All Cities / Municipalities</option>';

    if (provinceCode === "") {
        locationDropdown.disabled = true;
        await displaySearchResults();
        return;
    }

    try {
        const url = "https://psgc.cloud/api/provinces/" + provinceCode + "/cities-municipalities";
        const response = await fetch(url);
        const cityList = await response.json();

        cityList.sort(function (cityA, cityB) {
            return cityA.name.localeCompare(cityB.name);
        });

        for (let i = 0; i < cityList.length; i++) {
            const option = document.createElement("option");
            option.value = cityList[i].code;
            option.textContent = cityList[i].name;
            locationDropdown.appendChild(option);
        }

        locationDropdown.disabled = false;
    } catch (error) {
        console.log("Could not load cities and municipalities.");
    }

    await displaySearchResults();
}

async function displaySearchResults() {
    const searchText = searchInput.value.toLowerCase().trim();
    const selectedLocation = locationDropdown.value;

    resultsContainer.innerHTML = "";

    try {
        let url = "/scholarships/list.php?";
        url += "search=" + encodeURIComponent(searchText);

        if (selectedLocation !== "") {
            url += "&municipality=" + encodeURIComponent(selectedLocation);
        }

        const scholarships = await apiRequest(url);
        let resultCount = 0;

        for (let i = 0; i < scholarships.length; i++) {
            resultsContainer.appendChild(createScholarshipCard(scholarships[i]));
            resultCount++;
        }

        if (resultCount === 0) {
            const emptyMessage = document.createElement("p");
            emptyMessage.className = "empty-state";
            emptyMessage.textContent = "No scholarships found.";
            resultsContainer.appendChild(emptyMessage);
        }
    } catch (error) {
        resultsContainer.textContent = "Could not load scholarships.";
    }
}

provinceDropdown.addEventListener("change", function () {
    loadCitiesForProvince(provinceDropdown.value);
});

locationDropdown.addEventListener("change", function () {
    displaySearchResults();
});

searchInput.addEventListener("input", function () {
    displaySearchResults();
});

searchForm.addEventListener("submit", function (event) {
    event.preventDefault();
    displaySearchResults();
});

async function startSearchPage() {
    await loadProvinces();
    await displaySearchResults();
}

startSearchPage();
