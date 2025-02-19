(() => {
    const H = document.documentElement,
    I = "theme",
    S = (t) => {
        H.setAttribute("data-bs-" + I, t);
        localStorage.setItem(I, t);
        const i = document.getElementById(I + "-icon");
        i && (i.className = `bi bi-${t === "dark" ? "moon" : "sun"}-fill`);
    };
    window.toggleTheme = () => S(H.getAttribute("data-bs-" + I) === "dark" ? "light" : "dark");
    window.setTheme = S; //EXPOSE setHEME to the global scope
    S(localStorage.getItem(I) || (window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"));
})();

/*
(() => {
    const H = document.documentElement,
    I = "theme",
    S = (t) => {
        H.setAttribute("data-bs-" + I, t);
        localStorage.setItem(I, t);
        const i = document.getElementById(I + "-icon");
        i && (i.className = `bi bi-${t === "dark" ? "moon" : "sun"}-fill`);
    };
    window.toggleTheme = () => S(H.getAttribute("data-bs-" + I) === "dark" ? "light" : "dark");
    S(localStorage.getItem(I) || (window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"));
})();
*/

/*
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
*/
