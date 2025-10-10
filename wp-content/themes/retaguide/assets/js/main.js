/**
 * RetaGuide Main JavaScript
 *
 * @package RetaGuide
 * @since 1.0.0
 */

(function() {
    'use strict';

    /**
     * Initialize on DOM ready
     */
    function init() {
        setupLazyLoading();
        setupAccessibility();
        setupSmoothScroll();
        setupMobileNav();
    }

    /**
     * Setup lazy loading for images
     */
    function setupLazyLoading() {
        // Native lazy loading is handled by browser
        // This adds a class when image is loaded for CSS transitions
        const images = document.querySelectorAll('img[loading="lazy"]');
        
        images.forEach(img => {
            if (img.complete) {
                img.classList.add('loaded');
            } else {
                img.addEventListener('load', function() {
                    this.classList.add('loaded');
                });
            }
        });
    }

    /**
     * Enhance accessibility
     */
    function setupAccessibility() {
        // Add skip link
        const skipLink = document.createElement('a');
        skipLink.href = '#main-content';
        skipLink.className = 'skip-link';
        skipLink.textContent = 'Skip to main content';
        document.body.insertBefore(skipLink, document.body.firstChild);

        // Handle keyboard navigation
        document.addEventListener('keydown', function(e) {
            // Tab key
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-nav');
            }
        });

        // Remove class on mouse use
        document.addEventListener('mousedown', function() {
            document.body.classList.remove('keyboard-nav');
        });

        // ARIA live region for dynamic content
        const liveRegion = document.createElement('div');
        liveRegion.setAttribute('role', 'status');
        liveRegion.setAttribute('aria-live', 'polite');
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.className = 'sr-only';
        liveRegion.id = 'aria-live-region';
        document.body.appendChild(liveRegion);
    }

    /**
     * Smooth scroll for anchor links
     */
    function setupSmoothScroll() {
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                
                // Skip if it's just # or #0
                if (href === '#' || href === '#0') {
                    return;
                }

                const target = document.querySelector(href);
                
                if (target) {
                    e.preventDefault();
                    const headerOffset = 80;
                    const elementPosition = target.getBoundingClientRect().top;
                    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

                    window.scrollTo({
                        top: offsetPosition,
                        behavior: 'smooth'
                    });

                    // Focus target for accessibility
                    target.setAttribute('tabindex', '-1');
                    target.focus();
                }
            });
        });
    }

    /**
     * Mobile navigation enhancement
     */
    function setupMobileNav() {
        const navToggle = document.querySelector('.wp-block-navigation__responsive-container-open');
        
        if (navToggle) {
            navToggle.addEventListener('click', function() {
                const nav = document.querySelector('.wp-block-navigation');
                const isExpanded = this.getAttribute('aria-expanded') === 'true';
                
                // Trap focus in mobile nav when open
                if (!isExpanded) {
                    trapFocus(nav);
                }
            });
        }
    }

    /**
     * Trap focus within element (for modals, mobile nav)
     */
    function trapFocus(element) {
        const focusableElements = element.querySelectorAll(
            'a[href], button:not([disabled]), textarea, input, select'
        );
        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];

        element.addEventListener('keydown', function(e) {
            if (e.key !== 'Tab') return;

            if (e.shiftKey) {
                if (document.activeElement === firstFocusable) {
                    lastFocusable.focus();
                    e.preventDefault();
                }
            } else {
                if (document.activeElement === lastFocusable) {
                    firstFocusable.focus();
                    e.preventDefault();
                }
            }
        });
    }

    /**
     * Announce to screen readers
     */
    function announce(message) {
        const liveRegion = document.getElementById('aria-live-region');
        if (liveRegion) {
            liveRegion.textContent = message;
            setTimeout(() => {
                liveRegion.textContent = '';
            }, 1000);
        }
    }

    /**
     * Expose announce function globally
     */
    window.retaguide = {
        announce: announce
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
