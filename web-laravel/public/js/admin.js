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
    const cached = rows.map(row => ({row, text: normalizar(row.textContent), tags: (row.dataset.tags || '').split(' ')}));
    const update = () => {
        const term = normalizar(search?.value.trim() || '');
        const selected = filter?.value || '';
        let visible = 0;
        cached.forEach(({row, text, tags}) => {
            row.hidden = !text.includes(term) || (!!selected && !tags.includes(selected));
            if (!row.hidden) visible++;
        });
        if (count) count.textContent = `${visible} de ${rows.length} registros`;
        if (empty) empty.hidden = visible !== 0 || rows.length === 0;
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
