document.querySelectorAll('[data-planificador]').forEach(form => {
    const inicio = form.querySelector('[name="fechaInicio"]');
    const fin = form.querySelector('[name="fechaFin"]');
    const minimo = fin.min;
    const fechas = () => {
        fin.min = inicio.value || minimo;
        fin.required = !!inicio.value;
        inicio.required = !!fin.value;
    };
    inicio.addEventListener('change', fechas);
    fin.addEventListener('change', fechas);
    fechas();
});
const armarCarrusel = (carrusel, controles, tarjetaSelector, gap = 22) => {
    if (!controles) return;
    const anterior = controles.querySelector('[data-destinos-anterior], [data-sugerencias-anterior]');
    const siguiente = controles.querySelector('[data-destinos-siguiente], [data-sugerencias-siguiente]');
    const actualizar = () => {
        const limite = carrusel.scrollWidth - carrusel.clientWidth;
        controles.hidden = limite < 2;
        anterior.disabled = carrusel.scrollLeft < 2;
        siguiente.disabled = carrusel.scrollLeft >= limite - 2;
    };
    const desplazar = direccion => {
        const tarjeta = carrusel.querySelector(tarjetaSelector);
        const salto = (tarjeta?.getBoundingClientRect().width || carrusel.clientWidth) + gap;
        carrusel.scrollBy({left: direccion * salto, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth'});
    };
    anterior.addEventListener('click', () => desplazar(-1));
    siguiente.addEventListener('click', () => desplazar(1));
    carrusel.addEventListener('scroll', actualizar, {passive: true});
    window.addEventListener('resize', actualizar);
    actualizar();
};
document.querySelectorAll('[data-destinos-carrusel]').forEach(carrusel => {
    armarCarrusel(carrusel, carrusel.parentElement.querySelector('[data-destinos-controles]'), '.tarjeta-destino', 22);
});
document.querySelectorAll('[data-sugerencias-carrusel]').forEach(carrusel => {
    armarCarrusel(carrusel, carrusel.parentElement.querySelector('[data-sugerencias-controles]'), '.sugerencia-tarjeta', 14);
});

document.querySelectorAll('[data-vlog-showcase]').forEach(showcase => {
    const pista = showcase.querySelector('[data-vlog-pista]');
    const anterior = showcase.querySelector('[data-vlog-anterior]');
    const siguiente = showcase.querySelector('[data-vlog-siguiente]');
    if (!pista || !anterior || !siguiente) return;

    const slides = () => [...pista.querySelectorAll('[data-vlog-slide]')];
    const activo = () => {
        const centro = pista.scrollLeft + pista.clientWidth / 2;
        let mejor = slides()[0];
        let distancia = Infinity;
        slides().forEach(slide => {
            const mid = slide.offsetLeft + slide.offsetWidth / 2;
            const d = Math.abs(mid - centro);
            if (d < distancia) {
                distancia = d;
                mejor = slide;
            }
        });
        return mejor;
    };
    const ir = direccion => {
        const lista = slides();
        const actual = activo();
        const indice = Math.max(0, lista.indexOf(actual));
        const destino = lista[Math.min(lista.length - 1, Math.max(0, indice + direccion))];
        destino?.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth', inline: 'center', block: 'nearest' });
    };
    const actualizar = () => {
        const lista = slides();
        const actual = activo();
        const indice = lista.indexOf(actual);
        anterior.disabled = indice <= 0;
        siguiente.disabled = indice >= lista.length - 1;
        lista.forEach((slide, i) => slide.classList.toggle('activo', i === indice));
    };

    anterior.addEventListener('click', () => ir(-1));
    siguiente.addEventListener('click', () => ir(1));
    pista.addEventListener('scroll', actualizar, { passive: true });
    window.addEventListener('resize', actualizar);
    requestAnimationFrame(() => {
        slides()[0]?.scrollIntoView({ inline: 'center', block: 'nearest' });
        actualizar();
    });
});
