// This file controls the Settings page.

const helpLink = document.querySelector(".profile-menu .menu-item:nth-child(2)");

if (helpLink !== null) {
    helpLink.addEventListener("click", function (event) {
        event.preventDefault();
        alert("For help, please contact your SCHOOlar administrator.");
    });
}
