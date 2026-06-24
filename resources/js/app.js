import Alpine from 'alpinejs';
import Iodine from '@caneara/iodine';
import Swal from 'sweetalert2';

const iodine = new Iodine();
iodine.setErrorMessage('required',  'This field is required.');
iodine.setErrorMessage('maxLength', 'Must be [PARAM] characters or fewer.');

window.Alpine = Alpine;
window.Iodine = iodine;

// ─── SweetAlert2 helpers ───────────────────────────────────────────────────

function cssVar(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
}

function _dispatch(swalOptions, formOrCb) {
    Swal.fire(swalOptions).then((result) => {
        if (!result.isConfirmed) return;
        if (formOrCb instanceof HTMLElement) {
            formOrCb.submit();
        } else if (typeof formOrCb === 'function') {
            formOrCb();
        }
    });
}

/**
 * Destructive confirmation — delete course, unit, token, user, etc.
 * Confirm: error red. Cancel: neutral gray. Focus defaults to cancel.
 */
window.confirmDelete = function (label, formOrCb) {
    _dispatch({
        title: `Delete "${label}"?`,
        text: 'This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        reverseButtons: true,
        focusCancel: true,
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        confirmButtonColor: cssVar('--color-error'),           // #ba1a1a
        cancelButtonColor:  cssVar('--color-outline-variant'), // #c6c6cf
    }, formOrCb);
};

/**
 * Positive / safe confirmation — publish, approve, enroll, save, etc.
 * Confirm: gold (dark text via .swal-confirm-positive CSS override).
 * Cancel: neutral gray. Focus defaults to confirm.
 */
window.confirmAction = function (title, text, formOrCb) {
    _dispatch({
        title,
        text,
        icon: 'question',
        showCancelButton: true,
        reverseButtons: true,
        focusConfirm: true,
        confirmButtonText: 'Confirm',
        cancelButtonText: 'Cancel',
        confirmButtonColor: cssVar('--color-gold'),            // #FBB740
        cancelButtonColor:  cssVar('--color-outline-variant'), // #c6c6cf
        customClass: { confirmButton: 'swal-confirm-positive' },
    }, formOrCb);
};

/**
 * Neutral confirmation — logout, navigate away with unsaved changes, etc.
 * Both buttons neutral gray; confirm is slightly darker and gets focus.
 */
window.confirmNeutral = function (title, text, formOrCb) {
    _dispatch({
        title,
        text,
        icon: 'question',
        showCancelButton: true,
        reverseButtons: true,
        focusConfirm: true,
        confirmButtonText: 'Continue',
        cancelButtonText: 'Cancel',
        confirmButtonColor: cssVar('--color-outline'),         // #76777f
        cancelButtonColor:  cssVar('--color-outline-variant'), // #c6c6cf
    }, formOrCb);
};

// ─── Toast ────────────────────────────────────────────────────────────────

// type: 'success' | 'error' | 'warning' | 'info'
window.showToast = function (message, type = 'success') {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: type,
        title: message,
        showConfirmButton: false,
        timer: 4000,
        timerProgressBar: true,
    });
};

// ─── Flash messages from layout ───────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
    const f = window.__flash ?? {};
    if (f.success) window.showToast(f.success, 'success');
    if (f.error)   window.showToast(f.error,   'error');
    if (f.warning) window.showToast(f.warning,  'warning');
});

// ─── bfcache guard ────────────────────────────────────────────────────────
// DOMContentLoaded does not fire on bfcache restore; pageshow does, with
// event.persisted === true. Swal.close() is unreliable here because bfcache
// restores the frozen JS state — Swal may not consider the popup "open" even
// though its container node is present in the restored DOM. Removing the node
// directly is the reliable fix.
window.addEventListener('pageshow', (event) => {
    if (!event.persisted) return;
    document.querySelector('.swal2-container')?.remove();
    window.__flash = {}; // bfcache restores the original object; clear it so
                         // nothing can re-trigger toasts on this restored load
});

Alpine.start();
