/**
 * YakShaverCB Calendar - JavaScript functionality
 * (C) 2026 Yak Shaver https://www.kayakshaver.com All rights reserved.
 */

((document) => {
    'use strict';

    /** @type {bootstrap.Modal|null} */
    let eventModal = null;

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

        // Initialize event modal
        initEventModal(calendar);
    };

    /**
     * Initialize keyboard navigation
     * @param {HTMLElement} calendar - The calendar container element
     */
    const initKeyboardNavigation = (calendar) => {
        document.addEventListener('keydown', (event) => {
            // Only handle navigation when not in an input field and modal is not open
            if (event.target.matches('input, textarea, select')) {
                return;
            }

            // Don't handle navigation when modal is open (let Bootstrap handle Escape)
            const modalEl = document.getElementById('yscbcEventModal');
            if (modalEl && modalEl.classList.contains('show')) {
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

    /**
     * Initialize the event modal functionality
     * @param {HTMLElement} calendar - The calendar container element
     */
    const initEventModal = (calendar) => {
        const modalEl = document.getElementById('yscbcEventModal');
        if (!modalEl) {
            return;
        }

        // Initialize Bootstrap modal
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            eventModal = new bootstrap.Modal(modalEl);
        } else {
            // Fallback: try to initialize later when Bootstrap is loaded
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    eventModal = new bootstrap.Modal(modalEl);
                }
            });
        }

        // Add click handlers to all event links
        calendar.addEventListener('click', (event) => {
            const eventLink = event.target.closest('.yscbc-event[data-event-id]');
            if (eventLink) {
                event.preventDefault();
                const eventId = eventLink.dataset.eventId;
                const eventUrl = eventLink.dataset.eventUrl;
                showEventModal(calendar, eventId, eventUrl);
            }
        });

        // Reset modal content when hidden
        modalEl.addEventListener('hidden.bs.modal', () => {
            resetModalContent();
        });

        // Close modal when group link is clicked
        modalEl.addEventListener('click', (event) => {
            const groupLink = event.target.closest('.yscbc-modal-group-link');
            if (groupLink && eventModal) {
                eventModal.hide();
            }
        });
    };

    /**
     * Show the event modal with details fetched via AJAX
     * @param {HTMLElement} calendar - The calendar container element
     * @param {string} eventId - The event ID
     * @param {string} eventUrl - The full event URL (kept for fallback navigation)
     */
    const showEventModal = async (calendar, eventId, eventUrl) => {
        const modalEl = document.getElementById('yscbcEventModal');
        if (!modalEl) {
            // Fallback: navigate to event URL
            window.location.href = eventUrl;
            return;
        }

        // Show loading state
        showModalLoading();

        // Show the modal
        if (eventModal) {
            eventModal.show();
        } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            eventModal = new bootstrap.Modal(modalEl);
            eventModal.show();
        } else {
            // Bootstrap not available, fallback to direct navigation
            window.location.href = eventUrl;
            return;
        }

        // Fetch event details
        const ajaxUrl = calendar.dataset.ajaxUrl;
        if (!ajaxUrl) {
            showModalError('Configuration error');
            return;
        }

        try {
            const response = await fetch(`${ajaxUrl}&id=${encodeURIComponent(eventId)}`);
            const json = await response.json();

            if (!response.ok || json.error) {
                throw new Error(json.message || 'Failed to load event details');
            }

            populateModalContent(json.data);
        } catch (error) {
            showModalError(error.message || 'Failed to load event details');
        }
    };

    /**
     * Show the loading state in the modal
     */
    const showModalLoading = () => {
        const modalEl = document.getElementById('yscbcEventModal');
        if (!modalEl) return;

        const loading = modalEl.querySelector('.yscbc-modal-loading');
        const content = modalEl.querySelector('.yscbc-modal-content');
        const error = modalEl.querySelector('.yscbc-modal-error');

        if (loading) loading.style.display = 'flex';
        if (content) content.style.display = 'none';
        if (error) error.style.display = 'none';
    };

    /**
     * Show error message in the modal
     * @param {string} message - The error message
     */
    const showModalError = (message) => {
        const modalEl = document.getElementById('yscbcEventModal');
        if (!modalEl) return;

        const loading = modalEl.querySelector('.yscbc-modal-loading');
        const content = modalEl.querySelector('.yscbc-modal-content');
        const error = modalEl.querySelector('.yscbc-modal-error');
        const errorText = modalEl.querySelector('.yscbc-modal-error-text');

        if (loading) loading.style.display = 'none';
        if (content) content.style.display = 'none';
        if (error) error.style.display = 'block';
        if (errorText) errorText.textContent = message;
    };

    /**
     * Populate the modal with event data
     * @param {Object} eventData - The event data object
     */
    const populateModalContent = (eventData) => {
        const modalEl = document.getElementById('yscbcEventModal');
        if (!modalEl) return;

        const loading = modalEl.querySelector('.yscbc-modal-loading');
        const content = modalEl.querySelector('.yscbc-modal-content');
        const error = modalEl.querySelector('.yscbc-modal-error');

        // Hide loading, show content
        if (loading) loading.style.display = 'none';
        if (error) error.style.display = 'none';
        if (content) content.style.display = 'block';

        // Set modal title (screen reader) and visible title
        const modalTitle = modalEl.querySelector('#yscbcEventModalLabel');
        const modalTitleVisible = modalEl.querySelector('.yscbc-modal-title-text');
        if (modalTitle) {
            modalTitle.textContent = eventData.title || '';
        }
        if (modalTitleVisible) {
            modalTitleVisible.textContent = eventData.title || '';
        }

        // Set card accent color
        const modalCard = modalEl.querySelector('.yscbc-modal-card');
        if (modalCard && eventData.color) {
            modalCard.style.borderLeftColor = eventData.color;
        }

        // Apply status styling to match CBGroupJive events
        const statusValue = Number(eventData.status || 0);
        if (modalCard) {
            modalCard.classList.remove('gjGroupEventExpired', 'gjGroupEventActive', 'border-warning', 'border-primary');
            if (statusValue === 1) {
                modalCard.classList.add('gjGroupEventExpired', 'border-warning');
            } else if (statusValue === 2) {
                modalCard.classList.add('gjGroupEventActive', 'border-primary');
            }
        }

        // Set date
        const dateText = modalEl.querySelector('.yscbc-modal-date-text');
        if (dateText) {
            if (eventData.same_day) {
                dateText.textContent = eventData.start_date;
            } else {
                dateText.textContent = `${eventData.start_date} - ${eventData.end_date}`;
            }
        }

        // Set time
        const timeText = modalEl.querySelector('.yscbc-modal-time-text');
        if (timeText) {
            const timeRange = (eventData.start_time && eventData.end_time)
                ? `${eventData.start_time} - ${eventData.end_time}`
                : '';
            timeText.textContent = timeRange ? ` · ${timeRange}` : '';
        }

        // Set status marker (ended/now)
        const statusText = modalEl.querySelector('.yscbc-modal-status');
        if (statusText) {
            const statusEndedLabel = modalEl.dataset.statusEnded || '';
            const statusNowLabel = modalEl.dataset.statusNow || '';
            statusText.textContent = '';
            statusText.classList.remove('text-warning', 'text-primary');
            if (statusValue === 1 && statusEndedLabel) {
                statusText.textContent = ` · ${statusEndedLabel}`;
                statusText.classList.add('text-warning');
            } else if (statusValue === 2 && statusNowLabel) {
                statusText.textContent = ` · ${statusNowLabel}`;
                statusText.classList.add('text-primary');
            }
        }

        // Set location (hide if empty)
        const locationEl = modalEl.querySelector('.gjGroupEventLocation');
        const locationText = modalEl.querySelector('.yscbc-modal-location-text');
        if (locationEl && locationText) {
            const locationValue = eventData.location || eventData.address || '';
            if (locationValue) {
                locationEl.style.display = 'flex';
                locationText.textContent = locationValue;
            } else {
                locationEl.style.display = 'none';
            }
        }

        // Set group
        const groupRow = modalEl.querySelector('.gjGroupEventGroup');
        const groupLink = modalEl.querySelector('.yscbc-modal-group-link');
        const groupText = modalEl.querySelector('.yscbc-modal-group-text');
        if (groupRow && groupLink && groupText) {
            const groupName = eventData.group_name || '';
            if (groupName) {
                groupRow.style.display = 'flex';
                groupText.textContent = groupName;
                if (eventData.group_url) {
                    groupLink.href = eventData.group_url;
                } else {
                    groupLink.removeAttribute('href');
                }
            } else {
                groupRow.style.display = 'none';
            }
        }

        // Set description (as HTML)
        const description = modalEl.querySelector('.yscbc-modal-description');
        const descriptionWrapper = modalEl.querySelector('.yscbc-modal-description-wrapper');
        if (description) {
            if (eventData.description) {
                description.innerHTML = eventData.description;
                if (descriptionWrapper) {
                    descriptionWrapper.style.display = 'block';
                }
            } else {
                description.innerHTML = '';
                if (descriptionWrapper) {
                    descriptionWrapper.style.display = 'none';
                }
            }
        }
    };

    /**
     * Reset modal content to initial state
     */
    const resetModalContent = () => {
        const modalEl = document.getElementById('yscbcEventModal');
        if (!modalEl) return;

        const loading = modalEl.querySelector('.yscbc-modal-loading');
        const content = modalEl.querySelector('.yscbc-modal-content');
        const error = modalEl.querySelector('.yscbc-modal-error');

        if (loading) loading.style.display = 'flex';
        if (content) content.style.display = 'none';
        if (error) error.style.display = 'none';

        // Clear content
        const modalTitle = modalEl.querySelector('#yscbcEventModalLabel');
        const modalTitleVisible = modalEl.querySelector('.yscbc-modal-title-text');
        const dateText = modalEl.querySelector('.yscbc-modal-date-text');
        const timeText = modalEl.querySelector('.yscbc-modal-time-text');
        const statusText = modalEl.querySelector('.yscbc-modal-status');
        const locationText = modalEl.querySelector('.yscbc-modal-location-text');
        const groupLink = modalEl.querySelector('.yscbc-modal-group-link');
        const groupText = modalEl.querySelector('.yscbc-modal-group-text');
        const description = modalEl.querySelector('.yscbc-modal-description');
        const descriptionWrapper = modalEl.querySelector('.yscbc-modal-description-wrapper');
        const locationEl = modalEl.querySelector('.gjGroupEventLocation');
        const groupRow = modalEl.querySelector('.gjGroupEventGroup');
        const modalCard = modalEl.querySelector('.yscbc-modal-card');

        if (modalTitle) modalTitle.textContent = '';
        if (modalTitleVisible) modalTitleVisible.textContent = '';
        if (dateText) dateText.textContent = '';
        if (timeText) timeText.textContent = '';
        if (statusText) {
            statusText.textContent = '';
            statusText.classList.remove('text-warning', 'text-primary');
        }
        if (locationText) locationText.textContent = '';
        if (locationEl) locationEl.style.display = 'none';
        if (groupLink) {
            groupLink.removeAttribute('href');
        }
        if (groupText) groupText.textContent = '';
        if (groupRow) groupRow.style.display = 'none';
        if (description) description.innerHTML = '';
        if (descriptionWrapper) descriptionWrapper.style.display = 'none';
        if (modalCard) {
            modalCard.style.borderLeftColor = '';
            modalCard.classList.remove('gjGroupEventExpired', 'gjGroupEventActive', 'border-warning', 'border-primary');
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCalendar);
    } else {
        initCalendar();
    }

})(document);
