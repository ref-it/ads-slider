import './bootstrap';

import * as bootstrap from 'bootstrap'
window.bootstrap = bootstrap; // used for enabling tooltips

import $ from "jquery";
window.$ = window.jQuery = $;

// Updates an icon-picker's preview icon as its text input changes. Delegated
// on document (rather than Livewire's @script, which needs eval and breaks
// under Livewire's CSP-safe mode) so it keeps working across Livewire re-renders.
document.addEventListener('input', (event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || !input.classList.contains('icon-picker-input')) {
        return;
    }
    const addonId = input.dataset.iconAddon;
    const icon = addonId ? document.getElementById(addonId) : null;
    if (icon) {
        icon.className = `align-middle input-group-text fs-4 fas fa-${input.value}`;
    }
});

// Copies the AV link input's value to the clipboard. Delegated for the same
// CSP-safe-mode reason as the icon-picker listener above.
document.addEventListener('click', (event) => {
    const button = event.target.closest('#copyAVLink');
    if (!button) {
        return;
    }
    const input = document.getElementById('av_link');
    if (!(input instanceof HTMLInputElement)) {
        return;
    }
    input.select();
    navigator.clipboard.writeText(input.value);
});

// Submits a named form on click. Replaces an inline onclick= attribute (the
// logout link): CSP nonces only cover <script> tags, not onXXX= attributes.
document.addEventListener('click', (event) => {
    const link = event.target.closest('[data-submit-form]');
    if (!link) {
        return;
    }
    const form = document.getElementById(link.dataset.submitForm);
    if (form instanceof HTMLFormElement) {
        event.preventDefault();
        form.submit();
    }
});

// Confirms before submitting a form. Replaces an inline onsubmit= attribute,
// for the same CSP reason as the listener above.
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (form instanceof HTMLFormElement && form.dataset.confirm && !confirm(form.dataset.confirm)) {
        event.preventDefault();
    }
});

import.meta.glob([
    '../img/**',
    '../assets/fonts/**',
]);