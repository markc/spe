function setTheme(theme) {
    const htmlElement = document.documentElement;
    htmlElement.setAttribute("data-bs-theme", theme);
    localStorage.setItem("theme", theme);
    updateThemeIcon(theme);
}
function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute("data-bs-theme");
    setTheme(currentTheme === "dark" ? "light" : "dark");
}
function updateThemeIcon(theme) {
    const icon = document.getElementById("theme-icon");
    if (icon) {
        icon.className = theme === "dark" ? "bi bi-moon-fill" : "bi bi-sun-fill";
    }
}
const storedTheme = localStorage.getItem("theme");
if (storedTheme) {
    setTheme(storedTheme);
} else {
    const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
    setTheme(prefersDark ? "dark" : "light");
}

document.addEventListener("DOMContentLoaded", function() {
    const toastElList = document.querySelectorAll(".toast");
    toastElList.forEach(function(toastEl) {
        const toast = new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 3000
        });
        toast.show();
    });
});
function showToast(message, type) {
    const toastContainer = document.createElement("div");
    toastContainer.setAttribute("aria-live", "polite");
    toastContainer.setAttribute("aria-atomic", "true");
    toastContainer.style.position = "fixed";
    toastContainer.style.top = "20px";
    toastContainer.style.right = "20px";
    toastContainer.style.zIndex = "1050";

    toastContainer.innerHTML = 
        '<div class="toast align-items-center text-white bg-' + type + ' border-0" role="alert" aria-live="assertive" aria-atomic="true">' +
            '<div class="d-flex">' +
                '<div class="toast-body">' + message + '</div>' +
                '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
            '</div>' +
        '</div>';

    document.body.appendChild(toastContainer);
    const toastElement = toastContainer.querySelector(".toast");
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 3000
    });
    toast.show();

    toastElement.addEventListener("hidden.bs.toast", () => {
        toastContainer.remove();
    });
}
