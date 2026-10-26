document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', event => { if (!window.confirm(form.dataset.confirm)) event.preventDefault(); }));
document.querySelectorAll('form').forEach(form => form.addEventListener('submit', event => {
    if (event.defaultPrevented) return;
    const button = event.submitter;
    if (button && !form.hasAttribute('data-confirm')) { button.classList.add('submitting'); setTimeout(() => {button.disabled = true;}, 0); }
}));
document.querySelector('.menu-toggle')?.addEventListener('click', function () { const open = document.body.classList.toggle('nav-open'); this.setAttribute('aria-expanded', String(open)); });
document.addEventListener('keydown',event=> { if(event.key==='Escape') { document.body.classList.remove('nav-open'); document.querySelector('.menu-toggle')?.setAttribute('aria-expanded','false'); } });
