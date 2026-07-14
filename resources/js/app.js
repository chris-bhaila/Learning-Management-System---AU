import Alpine from 'alpinejs';
import Iodine from '@caneara/iodine';
import Swal from 'sweetalert2';

const iodine = new Iodine();
iodine.setErrorMessage('required',  'This field is required.');
iodine.setErrorMessage('maxLength', 'Must be [PARAM] characters or fewer.');
iodine.setErrorMessage('minLength', 'Must be at least [PARAM] characters.');
iodine.setErrorMessage('email',     'Must be a valid email address.');
iodine.setErrorMessage('same',      'Passwords do not match.');

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
window.confirmDelete = function (label, formOrCb, text = 'This cannot be undone.') {
    _dispatch({
        title: `Delete "${label}"?`,
        text,
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
 * Destructive confirmation with a custom title/confirm label — kick from class, remove
 * from course, etc. Same red/error styling as confirmDelete(), but that helper's title
 * and button text are hardcoded to "Delete", which doesn't fit destructive actions that
 * aren't literally a deletion.
 * Confirm: error red. Cancel: neutral gray. Focus defaults to cancel.
 */
window.confirmDestructive = function (title, text, formOrCb, confirmButtonText = 'Confirm') {
    _dispatch({
        title,
        text,
        icon: 'warning',
        showCancelButton: true,
        reverseButtons: true,
        focusCancel: true,
        confirmButtonText,
        cancelButtonText: 'Cancel',
        confirmButtonColor: cssVar('--color-error'),           // #ba1a1a
        cancelButtonColor:  cssVar('--color-outline-variant'), // #c6c6cf
    }, formOrCb);
};

/**
 * Destructive confirmation with a role-select input — demoting an admin down to
 * teacher/student, where the target role is picked as part of the confirmation itself.
 * Same red/error styling as confirmDelete()/confirmDestructive(); the selected value is
 * written into the given form's hidden `role` input before submit.
 * Confirm: error red. Cancel: neutral gray. Focus defaults to cancel.
 */
window.confirmDestructiveSelect = function (title, text, inputOptions, form, confirmButtonText = 'Confirm') {
    Swal.fire({
        title,
        text,
        icon: 'warning',
        input: 'select',
        inputOptions,
        inputPlaceholder: 'Select a role',
        showCancelButton: true,
        reverseButtons: true,
        focusCancel: true,
        confirmButtonText,
        cancelButtonText: 'Cancel',
        confirmButtonColor: cssVar('--color-error'),
        cancelButtonColor:  cssVar('--color-outline-variant'),
        inputValidator: (value) => !value ? 'Please select a role.' : undefined,
    }).then((result) => {
        if (!result.isConfirmed) return;
        form.querySelector('input[name="role"]').value = result.value;
        form.submit();
    });
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
 * Neutral confirmation — navigate away with unsaved changes, etc.
 * Both buttons neutral gray; confirm is slightly darker and gets focus.
 */
window.confirmNeutral = function (title, text, formOrCb, confirmButtonText = 'Continue', confirmButtonColor = cssVar('--color-outline')) {
    _dispatch({
        title,
        text,
        icon: 'question',
        showCancelButton: true,
        reverseButtons: true,
        focusConfirm: true,
        confirmButtonText,
        cancelButtonText: 'Cancel',
        confirmButtonColor,                                    // #76777f by default
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

// ─── Raw HTML editor toggle (TipTap ⇄ plain textarea) ─────────────────────
// Per-session UI only — no persistence. Shared by every <x-editor-raw-toggle>
// instance (course description + unit content, admin/teacher, create/show).
// Assumes exactly one active TipTap instance on the page at a time, exposed
// as window.tiptap, matching the existing convention in these editor pages.
window.rawHtmlToggle = function (hiddenInputId) {
    return {
        raw: false,
        error: '',

        toggle() {
            const hidden = document.getElementById(hiddenInputId);

            if (!this.raw) {
                this.$refs.rawTextarea.value = window.tiptap ? window.tiptap.getHTML() : (hidden?.value ?? '');
                this.raw = true;
                return;
            }

            this.error = '';
            try {
                if (window.tiptap) {
                    // Default emitUpdate re-fires the page's onUpdate handler,
                    // which syncs the hidden field from editor.getHTML().
                    window.tiptap.commands.setContent(this.$refs.rawTextarea.value);
                } else if (hidden) {
                    hidden.value = this.$refs.rawTextarea.value;
                }
                this.raw = false;
            } catch (e) {
                console.error('Raw HTML toggle: failed to load content back into the editor.', e);
                this.error = 'That HTML could not be loaded back into the editor. Fix the markup above, or keep editing here in raw mode.';
            }
        },

        onInput(value) {
            const hidden = document.getElementById(hiddenInputId);
            if (hidden) hidden.value = value;
        },
    };
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
