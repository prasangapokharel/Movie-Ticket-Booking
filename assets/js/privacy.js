// Function to show a Tailwind alert
function showAlert(message) {
    const alertDiv = document.createElement("div");
    alertDiv.className = "fixed top-5 right-5 bg-red-500 text-white px-4 py-2 rounded shadow-lg transition-opacity duration-300";
    alertDiv.innerHTML = message;

    document.body.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.classList.add("opacity-0");
        setTimeout(() => alertDiv.remove(), 500);
    }, 3000);
}

// Prevent Restricted Key Combinations
document.addEventListener("keydown", function (event) {
    // Prevent Snipping Tool (Win + Shift + S)
    if (event.key === "s" && event.shiftKey && event.metaKey) {
        event.preventDefault();
        showAlert("Snipping Tool is disabled.");
    }

    // Prevent Save Page (Ctrl + S)
    if (event.key === "s" && (event.ctrlKey || event.metaKey)) {
        event.preventDefault();
        showAlert("Saving is disabled for privacy.");
    }

    // Prevent Print (Ctrl + P)
    if (event.key === "p" && (event.ctrlKey || event.metaKey)) {
        event.preventDefault();
        showAlert("Printing is disabled.");
    }

    // Prevent View Source (Ctrl + U)
    if (event.key === "u" && (event.ctrlKey || event.metaKey)) {
        event.preventDefault();
        showAlert("Viewing source is disabled.");
    }

    // Prevent Developer Tools (Ctrl + Shift + I, F12)
    if ((event.key === "I" && event.ctrlKey && event.shiftKey) || event.key === "F12") {
        event.preventDefault();
        showAlert("Developer tools are disabled.");
    }
});

// Disable Right Click
document.addEventListener("contextmenu", function (event) {
    event.preventDefault();
    showAlert("Right-click is disabled.");
});

// Prevent Screenshot (Snipping Tool / PrintScreen) by Creating a Black Overlay
document.addEventListener("keyup", function (event) {
    if (event.key === "PrintScreen") {
        showAlert("Screenshots are disabled.");
        navigator.clipboard.writeText(""); // Clears clipboard

        // Create a full-screen black overlay
        const overlay = document.createElement("div");
        overlay.id = "screenshotBlocker";
        overlay.className = "fixed inset-0 bg-black opacity-100 z-50";
        document.body.appendChild(overlay);

        // Remove overlay after 1 second
        setTimeout(() => {
            overlay.remove();
        }, 1000);
    }
});

// Prevent Dragging of Images (to stop users from downloading images)
document.addEventListener("dragstart", function (event) {
    event.preventDefault();
});
