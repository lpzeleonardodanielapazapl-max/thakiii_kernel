document.querySelectorAll('.menu-toggle').forEach(button => {
    const menu = document.getElementById(button.getAttribute('aria-controls'));
    button.addEventListener('click', () => {
        const expanded = button.getAttribute('aria-expanded') !== 'true';
        button.setAttribute('aria-expanded', String(expanded));
        menu?.classList.toggle('abierto', expanded);
    });
    menu?.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
        button.setAttribute('aria-expanded', 'false');
        menu.classList.remove('abierto');
    }));
});
document.querySelectorAll('[data-imprimir]').forEach(button => button.addEventListener('click', () => window.print()));
const tituloOriginal = document.title;
window.addEventListener('beforeprint', () => {
    const informe = document.querySelector('[data-report-title]');
    if (informe) document.title = informe.dataset.reportTitle;
});
window.addEventListener('afterprint', () => { document.title = tituloOriginal; });
const mensajes = document.querySelector('.mensajes');
if (mensajes) mensajes.scrollTop = mensajes.scrollHeight;
document.querySelectorAll('form[method="POST"], form[method="post"]').forEach(form => {
    form.addEventListener('submit', event => {
        if (event.defaultPrevented) return;
        if (form.dataset.submitting === 'true') { event.preventDefault(); return; }
        form.dataset.submitting = 'true';
        form.querySelectorAll('button[type="submit"], button:not([type])').forEach(button => {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            if (!button.hasAttribute('data-chat-send')) {
                button.dataset.originalLabel = button.textContent;
                button.textContent = button.dataset.submittingLabel || 'Enviando…';
            }
        });
    });
    window.addEventListener('pageshow', () => {
        delete form.dataset.submitting;
        form.querySelectorAll('button[aria-busy="true"]').forEach(button => {
            button.disabled = false;
            button.removeAttribute('aria-busy');
            if (button.dataset.originalLabel) button.textContent = button.dataset.originalLabel;
        });
    });
});
