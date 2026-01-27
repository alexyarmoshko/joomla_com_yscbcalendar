/**
 * YakShaverCB Calendar - JavaScript functionality
 * (C) 2026 Yak Shaver https://www.kayakshaver.com All rights reserved.
 */

((document) => {
    'use strict';

    /**
     * Initialize the calendar functionality
     */
    const initCalendar = () => {
        const calendar = document.getElementById('yscbcalendar');
        if (!calendar) {
            return;
        }

        // Add keyboard navigation
        initKeyboardNavigation(calendar);

        // Add touch swipe support
        initTouchNavigation(calendar);
    };

    /**
     * Initialize keyboard navigation
     * @param {HTMLElement} calendar - The calendar container element
     */
    const initKeyboardNavigation = (calendar) => {
        document.addEventListener('keydown', (event) => {
            // Only handle navigation when not in an input field
            if (event.target.matches('input, textarea, select')) {
                return;
            }

            const prevBtn = calendar.querySelector('.yscbc-prev');
            const nextBtn = calendar.querySelector('.yscbc-next');
            const todayBtn = calendar.querySelector('.yscbc-today');

            switch (event.key) {
                case 'ArrowLeft':
                    if (prevBtn) {
                        event.preventDefault();
                        prevBtn.click();
                    }
                    break;

                case 'ArrowRight':
                    if (nextBtn) {
                        event.preventDefault();
                        nextBtn.click();
                    }
                    break;

                case 't':
                case 'T':
                    if (todayBtn && !event.ctrlKey && !event.metaKey) {
                        event.preventDefault();
                        todayBtn.click();
                    }
                    break;
            }
        });
    };

    /**
     * Initialize touch/swipe navigation
     * @param {HTMLElement} calendar - The calendar container element
     */
    const initTouchNavigation = (calendar) => {
        let touchStartX = 0;
        let touchEndX = 0;
        const minSwipeDistance = 50;

        calendar.addEventListener('touchstart', (event) => {
            touchStartX = event.changedTouches[0].screenX;
        }, { passive: true });

        calendar.addEventListener('touchend', (event) => {
            touchEndX = event.changedTouches[0].screenX;
            handleSwipe(calendar);
        }, { passive: true });

        const handleSwipe = (calendar) => {
            const swipeDistance = touchEndX - touchStartX;

            if (Math.abs(swipeDistance) < minSwipeDistance) {
                return;
            }

            if (swipeDistance > 0) {
                // Swipe right - go to previous
                const prevBtn = calendar.querySelector('.yscbc-prev');
                if (prevBtn) {
                    prevBtn.click();
                }
            } else {
                // Swipe left - go to next
                const nextBtn = calendar.querySelector('.yscbc-next');
                if (nextBtn) {
                    nextBtn.click();
                }
            }
        };
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCalendar);
    } else {
        initCalendar();
    }

})(document);
