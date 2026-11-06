(() => {
    const movimientoReducido = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (movimientoReducido.matches) return;
    const titular = document.querySelector('.pagina-publica .hero h1');
    if (titular) {
        const walker = document.createTreeWalker(titular, NodeFilter.SHOW_TEXT);
        const nodos = [];
        while (walker.nextNode()) if (walker.currentNode.textContent.trim()) nodos.push(walker.currentNode);
        let orden = 0;
        nodos.forEach(nodo => {
            const fragmento = document.createDocumentFragment();
            (nodo.textContent.match(/\S+\s*|\s+/g) || []).forEach(palabra => {
                if (!palabra.trim()) { fragmento.append(document.createTextNode(palabra)); return; }
                const span = document.createElement('span');
                span.className = 'liquid-palabra';
                span.style.setProperty('--orden', Math.min(orden++, 15));
                span.textContent = palabra;
                fragmento.append(span);
            });
            nodo.replaceWith(fragmento);
        });
    }
    if (!('IntersectionObserver' in window)) return;
    const piezas = document.querySelectorAll('.tarjeta-destino, .encabezado-seccion > *, .nosotros > *, .proceso article, .llamada > h2, .operaciones-acceso, .gestion-seccion, .perfil-seccion');
    const observador = new IntersectionObserver(entradas => entradas.forEach(entrada => {
        if (!entrada.isIntersecting) return;
        entrada.target.classList.add('liquid-visible');
        observador.unobserve(entrada.target);
    }), { threshold: 0.08 });
    piezas.forEach(pieza => { pieza.classList.add('liquid-entrada'); observador.observe(pieza); });
    movimientoReducido.addEventListener('change', evento => {
        if (!evento.matches) return;
        observador.disconnect();
        piezas.forEach(pieza => pieza.classList.add('liquid-visible'));
    });
})();
