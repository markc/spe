<?php

declare(strict_types=1);
// Created: 20150101 - Updated: 20250216
// Copyright (C) 2015-2025 Mark Constable <markc@renta.net> (AGPL-3.0)

namespace SPE\Autoload\Themes;

use SPE\Autoload\Core\{Ctx, Theme, Util};

class SideBar extends Theme
{
    public function __construct(private Ctx $ctx)
    {
        Util::elog(__METHOD__);
    }

    public function html(): string
    {
        Util::elog(__METHOD__);

        extract($this->ctx->out, EXTR_SKIP);

        return '<!DOCTYPE html>
<html lang="en" data-bs-theme="auto">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="dark light">
        <meta name="description" content="Simple PHP Example">
        <meta name="author" content="Mark Constable">
        <link rel="icon" href="favicon.ico">
        <title>' . $doc . '</title>' . $css . '
    </head>
    <body class="d-flex flex-column min-vh-100">' . $head . $main . $foot . $js . '
    </body>
</html>
';
    }

    public function css(): string
    {
        Util::elog(__METHOD__);

        return '
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
            <style>
                [data-bs-theme="light"] .nav-link {
                    color: #333333 !important;
                }
                [data-bs-theme="light"] .nav-link:hover {
                    color: #000000 !important;
                }
                [data-bs-theme="dark"] .nav-link {
                    color: #e0e0e0 !important;
                }
                [data-bs-theme="dark"] .nav-link:hover {
                    color: #ffffff !important;
                }
            </style>
            <style>
            .fw {
  width: 1em;
  display: inline-block;
  text-align: center;
  margin-right: 0.5rem;
}
[aria-expanded="true"] .chevron-icon {
  transform: rotate(90deg);
  transition: transform 0.2s ease-in-out;
}
[aria-expanded="false"] .chevron-icon {
  transform: rotate(0deg);
  transition: transform 0.2s ease-in-out;
}

body {
  min-height: 100vh;
  padding-top: 56px;
}
.navbar-height {
  height: 56px;
}
.wrapper {
  display: flex;
  min-height: calc(100vh - 56px);
}
.sidebar {
  width: 300px;
  padding-top: 20px;
  position: fixed;
  height: calc(100vh - 56px);
  overflow-y: auto;
  transition: margin-left 0.3s ease-in-out, margin-right 0.3s ease-in-out;
  z-index: 1000;
}
.sidebar.left {
  left: 0;
}
.sidebar.right {
  right: 0;
}
.sidebar.collapsed {
  margin-left: -300px;
}
.sidebar.right.collapsed {
  margin-right: -300px;
}
.sidebar .nav-link {
  padding: 10px 20px;
  display: flex;
  align-items: center;
  color: var(--bs-nav-link-color);
  text-decoration: none;
}
.sidebar .nav-link:hover {
  background-color: var(--bs-nav-link-hover-bg);
  color: var(--bs-nav-link-hover-color);
}
.main-content {
  margin-left: 300px;
  margin-right: 300px;
  flex-grow: 1;
  padding: 1rem;
  width: calc(100% - 600px);
  transition: margin-left 0.3s ease-in-out, margin-right 0.3s ease-in-out,
    width 0.3s ease-in-out;
}
.main-content.expanded-left {
  margin-left: 0;
  width: calc(100% - 300px);
}
.main-content.expanded-right {
  margin-right: 0;
  width: calc(100% - 300px);
}
.main-content.expanded-both {
  margin-left: 0;
  margin-right: 0;
  width: 100%;
}
.submenu {
  padding-left: 20px;
  background-color: var(--bs-tertiary-bg);
}
.submenu .nav-link {
  color: var(--bs-nav-link-color);
}
.submenu .nav-link:hover {
  background-color: var(--bs-nav-link-hover-bg);
  color: var(--bs-nav-link-hover-color);
}
.submenu .nav-link {
  padding: 8px 20px;
  font-size: 0.9rem;
}
.sidebar .nav-link svg {
  width: 1em;
  height: 1em;
  margin-right: 0.5em;
  fill: currentColor;
}
.sidebar-toggle {
  padding: 0.25rem 0.75rem;
}
#leftSidebarToggle {
  margin-right: 1rem;
}
#rightSidebarToggle {
  margin-left: 1rem;
}
/* Mobile-Friendly CSS */
@media (max-width: 768px) {
  .navbar-brand {
    margin-left: auto;
    margin-right: auto;
  }
  .sidebar {
    position: fixed;
    top: 56px;
    bottom: 0;
    background-color: var(--bs-tertiary-bg);
    z-index: 1030;
    width: 80%;
    max-width: 300px;
  }

  .sidebar.left {
    left: -100%;
    transition: left 0.3s ease-in-out;
    margin-left: 0;
  }

  .sidebar.right {
    right: -100%;
    transition: right 0.3s ease-in-out;
    margin-right: 0;
  }

  .sidebar.left.show {
    left: 0;
  }

  .sidebar.right.show {
    right: 0;
  }

  /* Main content adjustments */
  .main-content {
    margin-left: 0 !important;
    margin-right: 0 !important;
    width: 100% !important;
    padding: 10px;
    transition: none;
  }
  #leftSidebarToggle,
  #rightSidebarToggle {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 1031;
    padding: 0.25rem 0.75rem;
    margin: 0;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  #leftSidebarToggle {
    left: 0.5rem;
  }

  #rightSidebarToggle {
    right: 0.5rem;
  }

  .submenu {
    background-color: var(--bs-secondary-bg);
  }
}

/* Non-mobile styles */
@media (min-width: 769px) {
  /* Show sidebars by default on larger screens */
  .sidebar.left:not(.collapsed),
  .sidebar.right:not(.collapsed) {
    margin-left: 0;
    margin-right: 0;
  }
}
  </style>';
    }

    public function js(): string
    {
        Util::elog(__METHOD__);

        return '
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
            <script>
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
  const contentSection = document.getElementById("content-section");
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

  // AJAX Functions
  function showLoading() {
    contentSection.innerHTML =
      \'<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>\';
  }

  function handleError(error) {
    contentSection.innerHTML =
      \'<div class="alert alert-danger" role="alert">\' +
      "Error loading content: " +
      error.message +
      "</div>";
  }

  function updateURL(url) {
    history.pushState({}, "", url);
  }

  async function loadContent(url) {
    try {
      showLoading();
      const response = await fetch(url, {
        headers: {
          "X-Requested-With": "XMLHttpRequest"
        },
      });

      if (!response.ok) {
        throw new Error("HTTP error! status: " + response.status);
      }

      const data = await response.text();

      // Clear existing content first
      contentSection.innerHTML = "";

      // Update content
      contentSection.innerHTML = data;
      updateURL(url);

      // Update active states in navigation
      const currentPath = new URL(url).searchParams.get("o") || "Home";
      document.querySelectorAll("#leftSidebar .nav-link").forEach((link) => {
        link.classList.remove("active");
        if (
          link.href &&
          new URL(link.href).searchParams.get("o")?.toLowerCase() ===
            currentPath.toLowerCase()
        ) {
          link.classList.add("active");
        }
      });

      // Execute any inline scripts in the new content
      Array.from(contentSection.getElementsByTagName("script")).forEach(
        (script) => {
          const newScript = document.createElement("script");
          Array.from(script.attributes).forEach((attr) => {
            newScript.setAttribute(attr.name, attr.value);
          });
          newScript.textContent = script.textContent;
          script.parentNode.replaceChild(newScript, script);
        }
      );

      // Re-initialize theme after content load
      const storedTheme = localStorage.getItem("theme");
      if (storedTheme) {
        setTheme(storedTheme);
      } else {
        const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
        setTheme(prefersDark ? "dark" : "light");
      }

      if (window.innerWidth <= 768) {
        leftSidebar.classList.remove("show");
      }
    } catch (error) {
      handleError(error);
    }
  }

  // Debug function to log click events
  //function logClickDetails(event, element) {
  //    console.log("Click event:", {
  //        target: event.target,
  //        currentTarget: event.currentTarget,
  //        element: element,
  //        href: element?.href,
  //        classList: element?.classList
  //    });
  //}

  // Intercept left sidebar link clicks
  leftSidebar.addEventListener("click", function (event) {
    const link = event.target.closest("a");

    // If no link was clicked, exit early
    if (!link) return;

    // Debug logging
    //logClickDetails(event, link);

    // If the link is a collapse toggle, let it handle naturally
    if (link.getAttribute("data-bs-toggle") === "collapse") {
      return;
    }

    // At this point, we know it\'s a navigation link
    event.preventDefault();
    event.stopPropagation();

    // Check if we have a valid URL
    if (link.href) {
      loadContent(link.href);

      // Close mobile sidebar if needed
      if (window.innerWidth <= 768) {
        leftSidebar.classList.remove("show");
      }

      // If this is inside a collapse menu, keep it open
      const parentCollapse = link.closest(".collapse");
      if (parentCollapse) {
        parentCollapse.classList.add("show");
      }
    }
  });

  // Handle browser back/forward buttons
  window.addEventListener("popstate", function (event) {
    loadContent(window.location.href);
  });

  // Handle doc links in main content area
  document
    .getElementById("content-section")
    .addEventListener("click", function (event) {
      const docLink = event.target.closest(".doc-link");
      if (docLink) {
        event.preventDefault();
        loadContent(docLink.href);
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
        \'meta[name="csrf-token"]\'
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
          // Reload the content to show updated host list
          loadContent(window.location.href);
        })
        .catch((error) => {
          console.error("Error saving host:", error);
          alert("Failed to save host: " + error.message);
          // Keep the modal open on error
        });
    }
  });
});
            </script>';
    }

    public function doc(): string
    {
        Util::elog(__METHOD__);

        return $this->ctx->out['head'];
    }

    public function head(): string
    {
        Util::elog(__METHOD__);

        return '
            <nav class="navbar navbar-height navbar-expand-md bg-body-tertiary fixed-top border-bottom shadow-sm">
                <div class="container-fluid d-flex align-items-center">
                    <button class="btn" id="leftSidebarToggle" type="button">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <a class="navbar-brand mx-auto" href="/">
                        « ' . $this->ctx->out['doc'] . '
                    </a>
                    <div class="d-flex align-items-center">
                        <a class="nav-link" href="#" onclick="toggleTheme(); return false;">
                            <i id="theme-icon" class="bi bi-sun-fill"></i>
                        </a>
                        <button class="btn" id="rightSidebarToggle" type="button">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                    </div>
                </div>
            </nav>';
    }

    public function main(): string
    {
        Util::elog(__METHOD__);

        $lhsNav = $this->renderPluginNav($this->ctx->nav1 ?? []);
        $rhsNav = $this->renderPluginNav($this->ctx->nav2 ?? []);

        return '
            <div class="sidebar left bg-body-tertiary" id="leftSidebar">
                ' . $lhsNav . '
            </div>
            <div class="sidebar right bg-body-tertiary" id="rightSidebar">
                ' . $rhsNav . '
            </div>
            <div class="main-content" id="main">
                <div class="container-fluid">
                    <main class="content-section" id="content-section">
                        ' . $this->ctx->out['main'] . '
                    </main>
                </div>
            </div>';
    }

    public function foot(): string
    {
        Util::elog(__METHOD__);

        return '

        <footer class="bg-body-tertiary text-center py-3 mt-auto">
            <div class="container">
                <p class="text-muted mb-0"><small>[SideBar] ' . $this->ctx->out['foot'] . '</small></p>
            </div>
        </footer>';
    }

    public function renderPluginNav(array $navData): string
    {
        if (!isset($navData[0]))
        {
            return '';
        }

        // Since plugins use a fixed structure [section_name, items_array, icon],
        // we treat it as a dropdown
        return $this->renderDropdown(
            [
                $navData[0],  // Section name (e.g., "Plugins")
                $navData[1],  // Array of plugin items
                $navData[2]   // Section icon
            ]
        );
    }

    private function renderDropdown(array $section): string
    {
        $currentPlugin = $this->ctx->in['o'] ?? 'Home';
        $icon = isset($section[2]) ? '<i class="' . $section[2] . ' fw"></i> ' : '';

        $submenuItems = array_map(
            function ($item) use ($currentPlugin)
            {
                $isActive = strtolower($currentPlugin) === strtolower($item[0]) ? ' active' : '';
                $itemIcon = isset($item[2]) ? '<i class="' . $item[2] . ' fw"></i> ' : '';

                return '
                        <li class="nav-item">
                            <a class="nav-link' . $isActive . ' fw" href="' . $item[1] . '">' .
                    $itemIcon . $item[0] .
                    '</a>
                        </li>';
            },
            $section[1]
        );

        return '
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="collapse" href="#' . $section[0] . 'Submenu" 
                   role="button" aria-expanded="false" aria-controls="' . $section[0] . 'Submenu">' .
            $icon . $section[0] . ' <i class="bi bi-chevron-right chevron-icon fw ms-auto"></i>
                </a>
                <div class="collapse submenu" id="' . $section[0] . 'Submenu">
                    <ul class="nav flex-column">' .
            implode('', $submenuItems) . '
                    </ul>
                </div>
            </li>
        </ul>';
    }

    private function renderSingleNav(array $item): string
    {
        $currentPlugin = $this->ctx->in['o'] ?? 'Home';
        $isActive = $currentPlugin === $item[1] ? ' active' : '';
        $icon = isset($item[2]) ? '<i class="' . $item[2] . '"></i> ' : '';

        return '
        <ul class="nav flex-column">
            <li class="nav-item' . $isActive . '">
                <a class="nav-link" href="' . $item[1] . '">' . $icon . $item[0] . '</a>
            </li>
        </ul>';
    }
}
