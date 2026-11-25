// Perilaku kerangka halaman (sidebar mobile, auto-submit select). Kecil, dimuat di semua halaman.
const sidebar = document.querySelector('[data-sidebar]');
const backdrop = document.querySelector('[data-sidebar-backdrop]');
const toggle = document.querySelector('[data-sidebar-toggle]');

function setOpen(open) {
    sidebar?.setAttribute('data-open', String(open));
    backdrop?.classList.toggle('hidden', !open);
    toggle?.setAttribute('aria-expanded', String(open));
}

toggle?.addEventListener('click', () => setOpen(sidebar.dataset.open !== 'true'));
backdrop?.addEventListener('click', () => setOpen(false));
document.addEventListener('keydown', (e) => e.key === 'Escape' && setOpen(false));

document.querySelectorAll('[data-autosubmit]').forEach((form) => {
    form.addEventListener('change', () => form.requestSubmit());
});
