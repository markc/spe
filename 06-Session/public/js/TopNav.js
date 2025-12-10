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
