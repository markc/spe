(function() {
    const leftSidebar = document.getElementById('leftSidebar');
    const rightSidebar = document.getElementById('rightSidebar');
    const mainContent = document.getElementById('main');
    const leftToggle = document.getElementById('leftSidebarToggle');
    const rightToggle = document.getElementById('rightSidebarToggle');
    
    const MOBILE_BREAKPOINT = 768;
    const SIDEBAR_WIDTH = 250;
  
    function isMobile() {
      return window.innerWidth < MOBILE_BREAKPOINT;
    }
  
    function applyLayoutStyles() {
      if (leftSidebar) {
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
        mainContent.style.transition = 'margin 0.3s ease, width 0.3s ease';
        mainContent.style.position = 'relative';
      }
    }
  
    function updateContentLayout() {
      if (!mainContent) return;
      
      const leftVisible = leftSidebar && leftSidebar.classList.contains('show');
      const rightVisible = rightSidebar && rightSidebar.classList.contains('show');
      
      if (isMobile()) {
        mainContent.style.width = '100%';
        mainContent.style.marginLeft = '0';
        mainContent.style.marginRight = '0';
        
        if (leftSidebar) {
          leftSidebar.style.transform = leftVisible ? 'translateX(0)' : 'translateX(-100%)';
        }
        
        if (rightSidebar) {
          rightSidebar.style.transform = rightVisible ? 'translateX(0)' : 'translateX(100%)';
        }
        
        if (leftVisible || rightVisible) {
          addBackdrop();
        } else {
          removeBackdrop();
        }
      } else {
        if (leftSidebar) {
          leftSidebar.style.transform = leftVisible ? 'translateX(0)' : 'translateX(-100%)';
        }
        
        if (rightSidebar) {
          rightSidebar.style.transform = rightVisible ? 'translateX(0)' : 'translateX(100%)';
        }
        
        if (leftVisible && rightVisible) {
          mainContent.style.width = `calc(100% - ${2 * SIDEBAR_WIDTH}px)`;
          mainContent.style.marginLeft = `${SIDEBAR_WIDTH}px`;
          mainContent.style.marginRight = `${SIDEBAR_WIDTH}px`;
        } else if (leftVisible) {
          mainContent.style.width = `calc(100% - ${SIDEBAR_WIDTH}px)`;
          mainContent.style.marginLeft = `${SIDEBAR_WIDTH}px`;
          mainContent.style.marginRight = '0';
        } else if (rightVisible) {
          mainContent.style.width = `calc(100% - ${SIDEBAR_WIDTH}px)`;
          mainContent.style.marginLeft = '0';
          mainContent.style.marginRight = `${SIDEBAR_WIDTH}px`;
        } else {
          mainContent.style.width = '100%';
          mainContent.style.marginLeft = '0';
          mainContent.style.marginRight = '0';
        }
      }
    }
  
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
  
    function removeBackdrop() {
      const backdrop = document.querySelector('.sidebar-backdrop');
      if (backdrop) {
        backdrop.remove();
      }
    }
  
    function toggleSidebar(side) {
      const sidebar = side === 'left' ? leftSidebar : rightSidebar;
      if (!sidebar) return;
      
      sidebar.classList.toggle('show');
      updateContentLayout();
    }
  
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
      
      window.addEventListener('resize', () => {
        const wasMobile = isMobile();
        
        setTimeout(() => {
          const isNowMobile = isMobile();
          if (wasMobile !== isNowMobile) {
            setInitialState();
          }
          updateContentLayout();
        }, 100);
      });
    }
  
    function setInitialState() {
      const mobile = isMobile();
      
      if (leftSidebar) {
        leftSidebar.classList.toggle('show', !mobile);
      }
      
      if (rightSidebar) {
        rightSidebar.classList.toggle('show', !mobile);
      }
      
      removeBackdrop();
      updateContentLayout();
    }
  
    function init() {
      if (!mainContent) return;
      
      applyLayoutStyles();
      setInitialState();
      setupEventListeners();
    }
  
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
    } else {
      init();
    }
  })();
  