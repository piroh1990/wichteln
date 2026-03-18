/**
 * Shared JavaScript functions for Wichtlä.ch
 */

/**
 * Shows a toast notification
 * @param {string} message - The message to display
 * @param {string} type - The type of toast ('success' or 'error')
 */
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `notification ${type}`;
    // Accessibility attributes
    toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
    toast.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');

    toast.style.cssText = 'position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 1000; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 300px; justify-content: center;';
    toast.textContent = message;
    document.body.appendChild(toast);

    // Fade out and remove
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.5s ease';
        setTimeout(() => toast.remove(), 500);
    }, 3000);
}

/**
 * Copies text from an element to the clipboard
 * @param {string} elementId - The ID of the element containing the text/URL
 * @param {string} successMessage - Optional custom success message
 */
function copyToClipboard(elementId, successMessage = "Link erfolgreich kopiert! 📋") {
    var element = document.getElementById(elementId);
    if (!element) return;

    var copyText = element.getAttribute('data-url') || element.innerText || element.textContent;

    // Modern Clipboard API (preferred)
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(copyText).then(function() {
            showToast(successMessage);
        }).catch(function(err) {
            fallbackCopy(copyText, successMessage);
        });
    } else {
        fallbackCopy(copyText, successMessage);
    }
}

/**
 * Fallback for copying text to clipboard in older browsers
 * @param {string} text - The text to copy
 * @param {string} successMessage - Custom success message
 */
function fallbackCopy(text, successMessage) {
    var tempInput = document.createElement("textarea");
    tempInput.value = text;
    tempInput.style.position = "fixed";
    tempInput.style.opacity = "0";
    document.body.appendChild(tempInput);
    tempInput.select();
    tempInput.setSelectionRange(0, 99999);
    try {
        document.execCommand("copy");
        showToast(successMessage);
    } catch (err) {
        showToast("Fehler beim Kopieren. Bitte manuell kopieren.", "error");
    }
    document.body.removeChild(tempInput);
}

/**
 * Toggles an FAQ item
 * @param {HTMLElement} button - The clicked FAQ question button
 * @param {boolean} closeOthers - Whether to close other FAQ items
 */
function toggleFAQ(button, closeOthers = false) {
    const item = button.closest('.faq-item');
    if (!item) return;

    const isActive = item.classList.contains('active');

    if (closeOthers) {
        document.querySelectorAll('.faq-item').forEach(otherItem => {
            if (otherItem !== item) {
                otherItem.classList.remove('active');
                const otherBtn = otherItem.querySelector('.faq-question');
                if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    if (isActive) {
        item.classList.remove('active');
        button.setAttribute('aria-expanded', 'false');
    } else {
        item.classList.add('active');
        button.setAttribute('aria-expanded', 'true');
    }
}

/**
 * Handles form submission to show loading state
 * @param {HTMLFormElement} form - The form being submitted
 * @param {string} loadingText - Text to show during loading
 */
function handleFormSubmit(form, loadingText = 'Wird geladen...') {
    if (!form) return;

    form.addEventListener('submit', function(e) {
        if (e.defaultPrevented) return;

        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn && !submitBtn.disabled) {
            if (submitBtn.name) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = submitBtn.name;
                hiddenInput.value = submitBtn.value || '1';
                form.appendChild(hiddenInput);
            }
            submitBtn.style.minWidth = submitBtn.offsetWidth + 'px';
            submitBtn.style.whiteSpace = 'nowrap';
            submitBtn.innerHTML = `<span class="loading" aria-hidden="true"></span> ${loadingText}`;
            submitBtn.disabled = true;
            submitBtn.style.cursor = 'wait';
        }
    });
}
