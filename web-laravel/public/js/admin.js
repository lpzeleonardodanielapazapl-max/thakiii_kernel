document.documentElement.classList.add('js');
const menuButton = document.querySelector('.boton-menu');
if (menuButton) {
    const menu = document.getElementById(menuButton.getAttribute('aria-controls'));
    const toggle = open => {
        menuButton.setAttribute('aria-expanded', String(open));
        menu.classList.toggle('menu-abierto', open);
    };
    toggle(!window.matchMedia('(max-width: 760px)').matches);
    menuButton.addEventListener('click', () => toggle(menuButton.getAttribute('aria-expanded') !== 'true'));
}
const report = document.querySelector('body > .reporte-impresion');
let originalTitle = document.title;
let printing = false;
const preparePrint = () => {
    if (!report || printing) return;
    originalTitle = document.title;
    printing = true;
    document.title = report.dataset.reportTitle || originalTitle;
};
window.addEventListener('beforeprint', preparePrint);
window.addEventListener('afterprint', () => { document.title = originalTitle; printing = false; });
document.querySelectorAll('[data-print]').forEach(button => button.addEventListener('click', () => window.print()));
document.querySelectorAll('form').forEach(form => {
    if (form.method.toLowerCase() !== 'post') return;
    form.addEventListener('submit', event => {
        if (event.defaultPrevented) return;
        if (form.dataset.submitting === 'true') {
            event.preventDefault();
            return;
        }
        form.dataset.submitting = 'true';
        form.querySelectorAll('button[type="submit"]').forEach(button => {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
        });
    });
    window.addEventListener('pageshow', () => {
        delete form.dataset.submitting;
        form.querySelectorAll('button[aria-busy="true"]').forEach(button => {
            button.disabled = false;
            button.removeAttribute('aria-busy');
        });
    });
});
const normalizar = text => text.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es');
document.querySelectorAll('[data-table]').forEach(section => {
    const search = section.querySelector('[data-search]');
    const filter = section.querySelector('[data-filter]');
    const rows = [...section.querySelectorAll('tbody tr[data-row]')];
    const count = section.querySelector('[data-count]');
    const empty = section.querySelector('[data-no-results]');
    const printTable = document.getElementById(section.dataset.printTarget || '');
    const printReport = printTable?.closest('.reporte-impresion');
    const printRows = new Map([...printTable?.querySelectorAll('tbody tr[data-print-key]') || []].map(row => [row.dataset.printKey, row]));
    const cached = rows.map(row => ({row, text: normalizar(row.textContent), tags: (row.dataset.tags || '').split(' ')}));
    const update = () => {
        const term = normalizar(search?.value.trim() || '');
        const selected = filter?.value || '';
        let visible = 0;
        cached.forEach(({row, text, tags}) => {
            row.hidden = !text.includes(term) || (!!selected && !tags.includes(selected));
            const printRow = printRows.get(row.dataset.printKey);
            if (printRow) printRow.hidden = row.hidden;
            if (!row.hidden) visible++;
        });
        if (count) count.textContent = `${visible} de ${rows.length} registros`;
        if (empty) empty.hidden = visible !== 0 || rows.length === 0;
        if (printReport) {
            const filters = [];
            if (search?.value.trim()) filters.push('Búsqueda: ' + search.value.trim());
            if (selected) filters.push('Filtro: ' + filter.selectedOptions[0].textContent);
            printReport.querySelector('[data-report-count]').textContent = `${visible} de ${rows.length} registros`;
            printReport.querySelector('[data-report-filters]').textContent = filters.join(' · ') || 'Sin filtros adicionales.';
            const emptyReport = printTable.querySelector('[data-report-empty]');
            if (emptyReport) emptyReport.hidden = visible !== 0;
        }
    };
    search?.addEventListener('input', update);
    filter?.addEventListener('change', update);
    section.querySelector('[data-reset]')?.addEventListener('click', () => {
        if (search) search.value = '';
        if (filter) filter.value = '';
        update();
        search?.focus();
    });
    const initial = new URLSearchParams(window.location.search).get('filtro');
    if (filter && [...filter.options].some(option => option.value === initial)) filter.value = initial;
    update();
});
