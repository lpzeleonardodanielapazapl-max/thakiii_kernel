(() => {
    let consulta;
    const opciones = new WeakMap();
    const moneda = (precio, divisa) => precio === null || precio === undefined ? 'precio por confirmar' : `${precio} ${divisa}`;
    const cargar = url => {
        if (!consulta) consulta = fetch(url, {headers: {Accept: 'application/json'}, credentials: 'same-origin'})
            .then(respuesta => { if (!respuesta.ok) throw new Error('No pudimos cargar el catálogo. Cierra y vuelve a abrir estas opciones para reintentar.'); return respuesta.json(); })
            .catch(() => { consulta = undefined; throw new Error('No pudimos cargar las opciones. Comprueba tu conexión o sesión y vuelve a abrir este apartado.'); });
        return consulta;
    };
    const preparar = async panel => {
        const estado = panel.querySelector('[data-opcion-estado]');
        const selector = panel.querySelector('[data-opcion-catalogo]');
        const boton = panel.querySelector('[data-opcion-incluir]');
        selector.disabled = true; boton.disabled = true; estado.textContent = 'Consultando opciones…';
        try {
            const catalogo = await cargar(panel.dataset.catalogoUrl);
            const tipo = panel.querySelector('[data-opcion-tipo]').value;
            const lista = [];
            if (tipo === 'hoteles') (catalogo.hoteles || []).forEach(hotel => (hotel.habitaciones || []).forEach(habitacion => lista.push({
                label: `${hotel.nombre} · ${hotel.ciudad} · ${habitacion.nombre} · ${moneda(habitacion.precioNoche, habitacion.moneda)}/noche`,
                texto: `Alojamiento propuesto: ${hotel.nombre}, ${hotel.ciudad}${hotel.categoria ? ` (${hotel.categoria} estrellas)` : ''}. Habitación: ${habitacion.nombre}, hasta ${habitacion.capacidad} personas. Tarifa de referencia: ${moneda(habitacion.precioNoche, habitacion.moneda)}/noche.${Number(habitacion.desayuno) === 1 ? ' Incluye desayuno.' : ''}${hotel.servicios ? ` Comodidades: ${hotel.servicios.slice(0, 250)}.` : ''}`,
            })));
            if (tipo === 'servicios') (catalogo.servicios || []).forEach(servicio => lista.push({
                label: `${servicio.nombre} · ${servicio.tipo} · ${moneda(servicio.precio, servicio.moneda)}`,
                texto: `Traslado propuesto: ${servicio.nombre}. ${servicio.tipo}. Recorrido: ${servicio.origen} → ${servicio.destino}.${servicio.capacidad ? ` Hasta ${servicio.capacidad} pasajeros.` : ''} Precio base de referencia: ${moneda(servicio.precio, servicio.moneda)}.`,
            }));
            if (tipo === 'vehiculos') (catalogo.vehiculos || []).forEach(vehiculo => lista.push({
                label: `${vehiculo.tipo} · ${vehiculo.marca} ${vehiculo.modelo} · ${vehiculo.capacidad} pasajeros`,
                texto: `Vehículo sugerido: ${vehiculo.tipo}, ${vehiculo.marca} ${vehiculo.modelo}, hasta ${vehiculo.capacidad} pasajeros. Sujeto a confirmación para las fechas del viaje.`,
            }));
            opciones.set(panel, lista);
            selector.replaceChildren(new Option(lista.length ? 'Selecciona una opción' : 'No hay opciones activas para ofrecer', ''));
            lista.forEach((opcion, indice) => selector.append(new Option(opcion.label, String(indice))));
            selector.disabled = lista.length === 0;
            estado.textContent = lista.length ? 'Elige una opción; puedes incluir más de una alternativa en el mismo día.' : 'Registra una opción activa desde Gestión o describe el servicio para confirmar con el cliente.';
        } catch(error) { estado.textContent = error.message; }
    };
    document.addEventListener('toggle', event => {
        const panel = event.target;
        if (panel.matches?.('[data-itinerario-catalogo]') && panel.open) preparar(panel);
    }, true);
    document.addEventListener('change', event => {
        const panel = event.target.closest('[data-itinerario-catalogo]');
        if (!panel) return;
        if (event.target.matches('[data-opcion-tipo]')) preparar(panel);
        if (event.target.matches('[data-opcion-catalogo]')) panel.querySelector('[data-opcion-incluir]').disabled = event.target.value === '';
    });
    document.addEventListener('click', event => {
        const boton = event.target.closest('[data-opcion-incluir]');
        if (!boton) return;
        const panel = boton.closest('[data-itinerario-catalogo]');
        const selector = panel.querySelector('[data-opcion-catalogo]');
        const opcion = opciones.get(panel)?.[Number(selector.value)];
        if (selector.value === '' || !opcion) return;
        const campo = panel.closest('[data-dia]').querySelector('[data-dia-campo="servicios"]');
        const estado = panel.querySelector('[data-opcion-estado]');
        if (campo.value.includes(opcion.texto)) { estado.textContent = 'Esta opción ya está incluida en este día.'; return; }
        const siguiente = [campo.value.trim(), opcion.texto].filter(Boolean).join('\n\n');
        if (siguiente.length > campo.maxLength) { estado.textContent = 'El día ya tiene mucho detalle. Resume los servicios antes de incluir otra opción.'; return; }
        campo.value = siguiente; campo.dispatchEvent(new Event('input', {bubbles: true}));
        estado.textContent = 'Opción incluida en los servicios del día. Revisa el detalle antes de enviar la propuesta.';
    });
})();
