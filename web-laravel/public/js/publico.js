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
