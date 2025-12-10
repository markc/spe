/**
 * Container Width Indicator
 * A utility script to display the current width of the container element
 * with Bootstrap breakpoint information
 *
 * - Include this file in your HTML
 * - Call containerWidthIndicator.init() after your DOM is loaded
 * - Optionally call containerWidthIndicator.update() after any dynamic layout changes
 */
(function() {
  // Main content element reference
  let mainContent = null;

  /**
   * Create the width indicator element
   */
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

  /**
   * Update the width indicator text based on current container width
   */
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

  /**
   * Initialize the width indicator
   * @param {HTMLElement} contentElement - The main content element to measure
   */
  function init(contentElement) {
    mainContent = contentElement || document.getElementById('main');
    
    if (!mainContent) {
      console.error('Container width indicator: Main content element not found');
      return;
    }
    
    // Create the indicator
    createWidthIndicator();
    
    // Set up resize listener
    window.addEventListener('resize', () => {
      setTimeout(updateWidthIndicator, 100);
    });
    
    console.log('Container width indicator initialized');
  }

  // Expose public API
  window.containerWidthIndicator = {
    init: init,
    update: updateWidthIndicator
  };
})();
