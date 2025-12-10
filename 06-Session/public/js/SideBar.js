// Direct DOM manipulation approach for sidebar toggling
(function() {
    // DOM elements
    const leftSidebar = document.getElementById('leftSidebar');
    const rightSidebar = document.getElementById('rightSidebar');
    const mainContent = document.getElementById('main');
    const leftToggle = document.getElementById('leftSidebarToggle');
    const rightToggle = document.getElementById('rightSidebarToggle');
    
    // Constants
    const MOBILE_BREAKPOINT = 768;
    const SIDEBAR_WIDTH = 250;
  
    // Create width indicator
    function createWidthIndicator() {
      const existingIndicator = document.querySelector('.container-width-indicator');
      if (existingIndicator) {
        existingIndicator.remove();
      }
      
      const indicator = document.createElement('div');
      indicator.className = 'container-width-indicator';
      indicator.style.position = 'fixed';
      indicator.style.bottom = '20px';
      indicator.style.right = '20px';
      indicator.style.backgroundColor = 'rgba(220, 53, 69, 0.9)';
      indicator.style.color = 'white';
      indicator.style.padding = '8px 12px';
      indicator.style.borderRadius = '4px';
      indicator.style.fontSize = '0.875rem';
      indicator.style.fontWeight = 'bold';
      indicator.style.zIndex = '9999';
      indicator.style.border = '2px solid white';
      indicator.style.boxShadow = '0 0 10px rgba(0,0,0,0.5)';
      document.body.appendChild(indicator);
      updateWidthIndicator();
    }
  
    // Update width indicator
    function updateWidthIndicator() {
      const indicator = document.querySelector('.container-width-indicator');
      if (!indicator || !mainContent) return;
      
      const container = mainContent.querySelector('.container-fluid, .container') || mainContent;
      const width = container.clientWidth;
      
      if (width < 576) {
        indicator.textContent = `Container: XS (<576px) - ${width}px`;
      } else if (width < 768) {
        indicator.textContent = `Container: SM (≥576px) - ${width}px`;
      } else if (width < 992) {
        indicator.textContent = `Container: MD (≥768px) - ${width}px`;
      } else if (width < 1200) {
        indicator.textContent = `Container: LG (≥992px) - ${width}px`;
      } else if (width < 1400) {
        indicator.textContent = `Container: XL (≥1200px) - ${width}px`;
      } else {
        indicator.textContent = `Container: XXL (≥1400px) - ${width}px`;
      }
    }
  
    // Check if on mobile
    function isMobile() {
      return window.innerWidth < MOBILE_BREAKPOINT;
    }
  
    // Apply styles directly to elements rather than using CSS classes
    function applyLayoutStyles() {
      // Apply initial sidebar positioning
      if (leftSidebar) {
        // Common styles for left sidebar
        leftSidebar.style.position = 'fixed';
        leftSidebar.style.top = '56px';
        leftSidebar.style.bottom = '0';
        leftSidebar.style.left = '0';
        leftSidebar.style.width = SIDEBAR_WIDTH + 'px';
        leftSidebar.style.zIndex = '1030';
        leftSidebar.style.transition = 'transform 0.3s ease';
        leftSidebar.style.overflowY = 'auto';
      }
      
      if (rightSidebar) {
        // Common styles for right sidebar
        rightSidebar.style.position = 'fixed';
        rightSidebar.style.top = '56px';
        rightSidebar.style.bottom = '0';
        rightSidebar.style.right = '0';
        rightSidebar.style.width = SIDEBAR_WIDTH + 'px';
        rightSidebar.style.zIndex = '1030';
        rightSidebar.style.transition = 'transform 0.3s ease';
        rightSidebar.style.overflowY = 'auto';
      }
      
      if (mainContent) {
        // Main content styles
        mainContent.style.transition = 'margin 0.3s ease, width 0.3s ease';
        mainContent.style.position = 'relative';
      }
    }
  
    // Update content layout based on sidebar visibility
    function updateContentLayout() {
      if (!mainContent) return;
      
      const leftVisible = leftSidebar && leftSidebar.classList.contains('show');
      const rightVisible = rightSidebar && rightSidebar.classList.contains('show');
      
      if (isMobile()) {
        // Mobile: Overlay approach - content doesn't resize
        mainContent.style.width = '100%';
        mainContent.style.marginLeft = '0';
        mainContent.style.marginRight = '0';
        
        // Position sidebars for mobile (off-canvas when hidden)
        if (leftSidebar) {
          leftSidebar.style.transform = leftVisible ? 'translateX(0)' : 'translateX(-100%)';
        }
        
        if (rightSidebar) {
          rightSidebar.style.transform = rightVisible ? 'translateX(0)' : 'translateX(100%)';
        }
        
        // Add backdrop when sidebar is visible
        if (leftVisible || rightVisible) {
          addBackdrop();
        } else {
          removeBackdrop();
        }
      } else {
        // Desktop: Content reflow approach
        
        // Handle left sidebar
        if (leftSidebar) {
          leftSidebar.style.transform = leftVisible ? 'translateX(0)' : 'translateX(-100%)';
        }
        
        // Handle right sidebar
        if (rightSidebar) {
          rightSidebar.style.transform = rightVisible ? 'translateX(0)' : 'translateX(100%)';
        }
        
        // Adjust main content based on sidebar visibility
        if (leftVisible && rightVisible) {
          // Both sidebars visible
          mainContent.style.width = `calc(100% - ${2 * SIDEBAR_WIDTH}px)`;
          mainContent.style.marginLeft = `${SIDEBAR_WIDTH}px`;
          mainContent.style.marginRight = `${SIDEBAR_WIDTH}px`;
        } else if (leftVisible) {
          // Only left sidebar visible
          mainContent.style.width = `calc(100% - ${SIDEBAR_WIDTH}px)`;
          mainContent.style.marginLeft = `${SIDEBAR_WIDTH}px`;
          mainContent.style.marginRight = '0';
        } else if (rightVisible) {
          // Only right sidebar visible
          mainContent.style.width = `calc(100% - ${SIDEBAR_WIDTH}px)`;
          mainContent.style.marginLeft = '0';
          mainContent.style.marginRight = `${SIDEBAR_WIDTH}px`;
        } else {
          // No sidebars visible
          mainContent.style.width = '100%';
          mainContent.style.marginLeft = '0';
          mainContent.style.marginRight = '0';
        }
      }
      
      // Update indicator after layout changes
      setTimeout(updateWidthIndicator, 310);
    }
  
    // Add backdrop for mobile
    function addBackdrop() {
      if (document.querySelector('.sidebar-backdrop')) return;
      
      const backdrop = document.createElement('div');
      backdrop.className = 'sidebar-backdrop';
      backdrop.style.position = 'fixed';
      backdrop.style.top = '0';
      backdrop.style.left = '0';
      backdrop.style.right = '0';
      backdrop.style.bottom = '0';
      backdrop.style.backgroundColor = 'rgba(0,0,0,0.5)';
      backdrop.style.zIndex = '1025';
      
      backdrop.addEventListener('click', () => {
        if (leftSidebar && leftSidebar.classList.contains('show')) {
          toggleSidebar('left');
        }
        if (rightSidebar && rightSidebar.classList.contains('show')) {
          toggleSidebar('right');
        }
      });
      
      document.body.appendChild(backdrop);
    }
  
    // Remove backdrop
    function removeBackdrop() {
      const backdrop = document.querySelector('.sidebar-backdrop');
      if (backdrop) {
        backdrop.remove();
      }
    }
  
    // Toggle sidebar visibility
    function toggleSidebar(side) {
      const sidebar = side === 'left' ? leftSidebar : rightSidebar;
      if (!sidebar) return;
      
      const isVisible = sidebar.classList.toggle('show');
      console.log(`${side} sidebar toggled:`, isVisible);
      
      // Update layout
      updateContentLayout();
    }
  
    // Set up event listeners
    function setupEventListeners() {
      if (leftToggle) {
        leftToggle.addEventListener('click', (e) => {
          e.preventDefault();
          toggleSidebar('left');
        });
      }
      
      if (rightToggle) {
        rightToggle.addEventListener('click', (e) => {
          e.preventDefault();
          toggleSidebar('right');
        });
      }
      
      // Handle window resize
      window.addEventListener('resize', () => {
        const wasMobile = isMobile();
        
        // Small delay to get accurate window size after resize
        setTimeout(() => {
          const isNowMobile = isMobile();
          
          // If changing between mobile/desktop, reset layout
          if (wasMobile !== isNowMobile) {
            console.log('View changed:', isNowMobile ? 'Mobile' : 'Desktop');
            setInitialState();
          }
          
          updateContentLayout();
          updateWidthIndicator();
        }, 100);
      });
    }
  
    // Set initial state
    function setInitialState() {
      const mobile = isMobile();
      console.log('Device detected:', mobile ? 'Mobile' : 'Desktop');
      
      // On desktop: show sidebars, on mobile: hide sidebars
      if (leftSidebar) {
        leftSidebar.classList.toggle('show', !mobile);
      }
      
      if (rightSidebar) {
        rightSidebar.classList.toggle('show', !mobile);
      }
      
      // Remove any backdrop
      removeBackdrop();
      
      // Update layout based on new state
      updateContentLayout();
    }
  
    // Initialize
    function init() {
      console.log('Elements found:', {
        leftSidebar: !!leftSidebar,
        rightSidebar: !!rightSidebar,
        mainContent: !!mainContent,
        leftToggle: !!leftToggle,
        rightToggle: !!rightToggle
      });
      
      if (!mainContent) {
        console.error('Main content element not found');
        return;
      }
      
      // Apply direct styles to elements
      applyLayoutStyles();
      
      // Set initial state
      setInitialState();
      
      // Set up event listeners
      setupEventListeners();
      
      // Create width indicator
      createWidthIndicator();
      
      console.log('Direct DOM manipulation sidebar solution initialized');
    }
  
    // Run when DOM is ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
    } else {
      init();
    }
  })();