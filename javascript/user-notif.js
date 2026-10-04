// This file controls the Notifications page.

const notificationContainer = document.querySelector(".notif-contents");

async function displayNotifications() {
    notificationContainer.innerHTML = "";

    try {
        const notifications = await apiRequest("/user/notifications.php");

        if (notifications.length === 0) {
            notificationContainer.textContent = "You have no notifications.";
            return;
        }

        for (let i = 0; i < notifications.length; i++) {
            const notification = document.createElement("div");
            notification.className = "notification-item";

            const title = document.createElement("h4");
            title.textContent = notifications[i].title;

            const message = document.createElement("p");
            message.textContent = notifications[i].message;

            const date = document.createElement("small");
            date.textContent = notifications[i].date;

            notification.appendChild(title);
            notification.appendChild(message);
            notification.appendChild(date);
            notificationContainer.appendChild(notification);
        }
    } catch (error) {
        notificationContainer.textContent = "Could not load notifications.";
    }
}

displayNotifications();
