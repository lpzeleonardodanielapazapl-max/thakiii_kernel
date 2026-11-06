document.documentElement.classList.add('js');
const menuButton = document.querySelector('.boton-menu');
if (menuButton) {
    const menu = document.getElementById(menuButton.getAttribute('aria-controls'));
    const toggle = open => {
        menuButton.setAttribute('aria-expanded', String(open));
        menu.classList.toggle('menu-abierto', open);
    };
    const pantallaCompacta = window.matchMedia('(max-width: 900px)');
    toggle(!pantallaCompacta.matches);
    pantallaCompacta.addEventListener('change', event => toggle(!event.matches));
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
        form.querySelectorAll('button[type="submit"], button:not([type])').forEach(button => {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            if (button.dataset.submittingLabel && !button.hasAttribute('data-chat-send')) {
                button.dataset.originalLabel = button.textContent;
                button.textContent = button.dataset.submittingLabel;
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
document.querySelectorAll('[data-itinerario-editor]').forEach(editor => {
    const lista = editor.querySelector('[data-dias-lista]');
    const plantilla = editor.querySelector('[data-dia-template]');
    const agregar = editor.querySelector('[data-dia-agregar]');
    const cantidad = editor.querySelector('[data-dia-cantidad]');
    const actualizar = () => {
        const dias = [...lista.children];
        dias.forEach((dia, indice) => {
            dia.querySelector('[data-dia-titulo]').textContent = `Día ${indice + 1}`;
            dia.querySelector('[data-dia-controles]').hidden = false;
            dia.querySelector('[data-dia-accion="subir"]').disabled = indice === 0;
            dia.querySelector('[data-dia-accion="bajar"]').disabled = indice === dias.length - 1;
            dia.querySelector('[data-dia-accion="quitar"]').disabled = dias.length === 1;
            dia.querySelectorAll('[data-dia-campo]').forEach(campo => {
                const nombre = campo.dataset.diaCampo;
                campo.name = `dias[${indice}][${nombre}]`;
                campo.id = `dia-${indice}-${nombre}`;
                dia.querySelector(`[data-dia-label="${nombre}"]`).htmlFor = campo.id;
                const error = campo.parentElement.querySelector('.error-campo');
                if (error) {
                    error.id = `error-dias-${indice}-${nombre}`;
                    campo.setAttribute('aria-describedby', error.id);
                }
            });
        });
        cantidad.textContent = `${dias.length} ${dias.length === 1 ? 'día' : 'días'} en el itinerario`;
        agregar.disabled = dias.length >= 120;
    };
    agregar.hidden = false;
    agregar.addEventListener('click', () => {
        if (lista.children.length >= 120) return;
        lista.append(plantilla.content.cloneNode(true));
        actualizar();
        lista.lastElementChild.querySelector('textarea').focus();
    });
    editor.addEventListener('click', event => {
        const boton = event.target.closest('[data-dia-accion]');
        if (!boton || boton.disabled) return;
        const dia = boton.closest('[data-dia]');
        const anterior = dia.previousElementSibling;
        const siguiente = dia.nextElementSibling;
        if (boton.dataset.diaAccion === 'subir' && anterior) lista.insertBefore(dia, anterior);
        if (boton.dataset.diaAccion === 'bajar' && siguiente) lista.insertBefore(siguiente, dia);
        if (boton.dataset.diaAccion === 'quitar' && lista.children.length > 1) {
            dia.remove();
            (siguiente || anterior)?.querySelector('textarea').focus();
        }
        actualizar();
        if (dia.isConnected) {
            const control = dia.querySelector(`[data-dia-accion="${boton.dataset.diaAccion}"]`);
            (control?.disabled ? dia.querySelector('textarea') : control)?.focus();
        }
    });
    actualizar();
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
