(() => {
    const lightbox = document.querySelector('[data-vlog-lightbox]');
    if (!lightbox) return;

    const imagen = lightbox.querySelector('[data-vlog-lightbox-img]');
    const contador = lightbox.querySelector('[data-vlog-lightbox-count]');
    const anterior = lightbox.querySelector('[data-vlog-lightbox-prev]');
    const siguiente = lightbox.querySelector('[data-vlog-lightbox-next]');
    const cerrar = lightbox.querySelectorAll('[data-vlog-lightbox-cerrar]');
    let fotos = [];
    let indice = 0;

    const pintar = () => {
        if (!fotos.length) return;
        imagen.src = fotos[indice];
        imagen.alt = `Foto ${indice + 1} de ${fotos.length}`;
        if (contador) contador.textContent = `${String(indice + 1).padStart(2, '0')} / ${String(fotos.length).padStart(2, '0')}`;
        const varias = fotos.length > 1;
        anterior.hidden = !varias;
        siguiente.hidden = !varias;
        anterior.disabled = indice <= 0;
        siguiente.disabled = indice >= fotos.length - 1;
    };

    const abrir = (lista, inicio = 0) => {
        fotos = Array.isArray(lista) ? lista.filter(Boolean) : [];
        if (!fotos.length) return;
        indice = Math.min(Math.max(0, inicio), fotos.length - 1);
        lightbox.hidden = false;
        document.body.classList.add('vlog-lightbox-abierto');
        pintar();
        cerrar[0]?.focus?.();
    };

    const cerrarLightbox = () => {
        lightbox.hidden = true;
        document.body.classList.remove('vlog-lightbox-abierto');
        imagen.removeAttribute('src');
        fotos = [];
        indice = 0;
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-vlog-abrir]');
        if (!trigger) return;
        event.preventDefault();
        let lista = [];
        try {
            lista = JSON.parse(trigger.getAttribute('data-vlog-fotos') || '[]');
        } catch (_) {
            lista = [];
        }
        const inicio = Number(trigger.getAttribute('data-vlog-inicio') || 0);
        abrir(lista, inicio);
    });

    anterior?.addEventListener('click', () => {
        if (indice > 0) {
            indice -= 1;
            pintar();
        }
    });
    siguiente?.addEventListener('click', () => {
        if (indice < fotos.length - 1) {
            indice += 1;
            pintar();
        }
    });
    cerrar.forEach((boton) => boton.addEventListener('click', cerrarLightbox));
    document.addEventListener('keydown', (event) => {
        if (lightbox.hidden) return;
        if (event.key === 'Escape') cerrarLightbox();
        if (event.key === 'ArrowLeft' && indice > 0) {
            indice -= 1;
            pintar();
        }
        if (event.key === 'ArrowRight' && indice < fotos.length - 1) {
            indice += 1;
            pintar();
        }
    });
})();
