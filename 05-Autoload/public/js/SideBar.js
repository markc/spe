// Theme initialization
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

document.addEventListener("DOMContentLoaded", function () {
    // Sidebar elements
    const leftSidebar = document.getElementById("leftSidebar");
    const rightSidebar = document.getElementById("rightSidebar");
    const mainContent = document.getElementById("main");
    const isMobile = window.innerWidth <= 768;

    // Handle left sidebar toggle
    document
        .getElementById("leftSidebarToggle")
        .addEventListener("click", function () {
            if (isMobile) {
                leftSidebar.classList.toggle("show");
                rightSidebar.classList.remove("show");
            } else {
                leftSidebar.classList.toggle("collapsed");
                if (rightSidebar.classList.contains("collapsed")) {
                    mainContent.classList.toggle("expanded-both");
                    mainContent.classList.toggle("expanded-right");
                } else {
                    mainContent.classList.toggle("expanded-left");
                }
            }
        });

    // Handle right sidebar toggle
    document
        .getElementById("rightSidebarToggle")
        .addEventListener("click", function () {
            if (isMobile) {
                rightSidebar.classList.toggle("show");
                leftSidebar.classList.remove("show");
            } else {
                rightSidebar.classList.toggle("collapsed");
                if (leftSidebar.classList.contains("collapsed")) {
                    mainContent.classList.toggle("expanded-both");
                    mainContent.classList.toggle("expanded-left");
                } else {
                    mainContent.classList.toggle("expanded-right");
                }
            }
        });

    // Close sidebars when clicking outside on mobile
    document.addEventListener("click", function (event) {
        if (isMobile) {
            const isClickInsideLeftSidebar = leftSidebar.contains(event.target);
            const isClickInsideRightSidebar = rightSidebar.contains(event.target);
            const isClickOnLeftToggle = event.target.closest("#leftSidebarToggle");
            const isClickOnRightToggle = event.target.closest("#rightSidebarToggle");

            if (
                !isClickInsideLeftSidebar &&
                !isClickOnLeftToggle &&
                leftSidebar.classList.contains("show")
            ) {
                leftSidebar.classList.remove("show");
            }
            if (
                !isClickInsideRightSidebar &&
                !isClickOnRightToggle &&
                rightSidebar.classList.contains("show")
            ) {
                rightSidebar.classList.remove("show");
            }
        }
    });

    // Handle window resize
    window.addEventListener("resize", function () {
        const newIsMobile = window.innerWidth <= 768;
        if (newIsMobile !== isMobile) {
            location.reload();
        }
    });

    // Handle host form submission
    document.addEventListener("click", function (event) {
        if (event.target && event.target.id === "saveHost") {
            const hostForm = document.getElementById("hostForm");
            console.log("Save host button clicked");
            // Get form elements
            const nameEl = document.getElementById("hostName");
            const hostnameEl = document.getElementById("hostHostname");
            const portEl = document.getElementById("hostPort");
            const usernameEl = document.getElementById("hostUsername");
            const identityFileEl = document.getElementById("hostIdentityFile");

            // Validate required elements exist
            if (!nameEl || !hostnameEl) {
                console.error("Required form elements not found");
                alert("Error: Form is incomplete or not properly loaded");
                return;
            }

            // Validate required fields have values
            if (!nameEl.value.trim() || !hostnameEl.value.trim()) {
                console.error("Required fields are empty");
                alert("Error: Name and hostname are required");
                return;
            }

            const formData = {
                name: nameEl.value.trim(),
                hostname: hostnameEl.value.trim(),
                port: (portEl && portEl.value.trim()) || "22",
                username: (usernameEl && usernameEl.value.trim()) || "root",
                identity_file: identityFileEl ? identityFileEl.value.trim() : "",
            };
            console.log("Form data:", formData);

            // Get CSRF token
            const csrfToken = document.querySelector(
                'meta[name="csrf-token"]'
            ).content;

            // Make API call to create host
            console.log("Making API request to create host...");
            // Construct the API URL using the base URL
            const baseUrl = new URL(
                window.location.origin + window.location.pathname
            );
            baseUrl.searchParams.set("plugin", "sshm");
            baseUrl.searchParams.set("api", "create_host");
            fetch(baseUrl.toString(), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": csrfToken,
                    "X-CSRF-Token": csrfToken, // Add both variations for compatibility
                },
                body: JSON.stringify(formData),
            })
                .then(async (response) => {
                    const responseData = await response.json();
                    console.log("API response:", {
                        status: response.status,
                        statusText: response.statusText,
                        data: responseData,
                    });
                    if (!response.ok) {
                        throw new Error(responseData.error || "Server error");
                    }
                    // Close the modal
                    const modal = bootstrap.Modal.getInstance(
                        document.getElementById("hostModal")
                    );
                    modal.hide();
                    // Reload the page to show updated host list
                    window.location.reload();
                })
                .catch((error) => {
                    console.error("Error saving host:", error);
                    alert("Failed to save host: " + error.message);
                    // Keep the modal open on error
                });
        }
    });
});
