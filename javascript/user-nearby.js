// This file controls the Nearby Scholarships page.

const nearbyMunicipality = document.getElementById("municipality");
const nearbyBarangay = document.getElementById("barangay");
const nearbyResults = document.querySelector(".display-content");

async function loadNearbyMunicipalities() {
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
            nearbyMunicipality.appendChild(option);
        }
    } catch (error) {
        console.log("Could not load municipalities.");
    }
}

async function loadNearbyBarangays(cityCode) {
    nearbyBarangay.innerHTML = '<option value="">All Barangays</option>';

    if (cityCode === "") {
        nearbyBarangay.disabled = true;
        await displayNearbyScholarships();
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
            nearbyBarangay.appendChild(option);
        }

        nearbyBarangay.disabled = false;
    } catch (error) {
        console.log("Could not load barangays.");
    }

    await displayNearbyScholarships();
}

async function displayNearbyScholarships() {
    nearbyResults.innerHTML = "";

    let url = "/scholarships/list.php?";

    if (nearbyMunicipality.value !== "") {
        url += "municipality=" + encodeURIComponent(nearbyMunicipality.value);
    }

    if (nearbyBarangay.value !== "") {
        if (!url.endsWith("?")) {
            url += "&";
        }
        url += "barangay=" + encodeURIComponent(nearbyBarangay.value);
    }

    try {
        const scholarships = await apiRequest(url);

        if (scholarships.length === 0) {
            const emptyMessage = document.createElement("p");
            emptyMessage.className = "empty-state";
            emptyMessage.textContent = "No nearby scholarships to show.";
            nearbyResults.appendChild(emptyMessage);
            return;
        }

        for (let i = 0; i < scholarships.length; i++) {
            nearbyResults.appendChild(createScholarshipCard(scholarships[i]));
        }
    } catch (error) {
        nearbyResults.textContent = "Could not load nearby scholarships.";
    }
}

nearbyMunicipality.addEventListener("change", function () {
    loadNearbyBarangays(nearbyMunicipality.value);
});

nearbyBarangay.addEventListener("change", function () {
    displayNearbyScholarships();
});

async function startNearbyPage() {
    await loadNearbyMunicipalities();
    await displayNearbyScholarships();
}

startNearbyPage();
