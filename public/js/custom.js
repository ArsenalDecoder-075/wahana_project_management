$(document).ready(function() {
    // Function to auto-dismiss alert messages after a certain time (e.g., 3 seconds)
    function autoDismissAlerts() {
        $('#success-message, #error-message').delay(3000).fadeOut('slow', function() {
            $(this).remove();
        });
    }

    // Call the function to dismiss the alerts after 3 seconds
    autoDismissAlerts();
});

// Circular Progress Bar Initialization
document.addEventListener("DOMContentLoaded", function () {
    const progressCircles = document.querySelectorAll(".progress-circle");
    progressCircles.forEach(function (circle) {
        const value = circle.getAttribute("data-value");
        const radius = circle.clientWidth / 2 - 5;
        const circumference = 2 * Math.PI * radius;
        const offset = circumference - (value / 100) * circumference;

        const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
        svg.setAttribute("width", "100%");
        svg.setAttribute("height", "100%");
        svg.innerHTML = `<circle class="progress-circle-bar" cx="50%" cy="50%" r="${radius}" stroke-dasharray="${circumference}" stroke-dashoffset="${offset}"></circle>`;
        circle.appendChild(svg);
    });
});
