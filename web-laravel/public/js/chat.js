document.querySelectorAll('[data-chat]').forEach(chat => {
    const historial = chat.querySelector('[data-chat-historial]');
    const final = chat.querySelector('[data-chat-final]');
    const form = chat.querySelector('[data-chat-form]');
    const campo = chat.querySelector('[data-chat-input]');
    const enviar = chat.querySelector('[data-chat-send]');
    const actualizarFinal = () => {
        final.hidden = historial.scrollHeight - historial.scrollTop - historial.clientHeight < 120;
    };
    const irAlFinal = () => { historial.scrollTop = historial.scrollHeight; actualizarFinal(); };
    requestAnimationFrame(irAlFinal);
    historial.addEventListener('scroll', actualizarFinal, { passive: true });
    final.addEventListener('click', irAlFinal);
    if (!campo || !form) return;
    const ajustar = () => {
        campo.setCustomValidity('');
        campo.style.height = 'auto';
        campo.style.height = Math.min(campo.scrollHeight, 150) + 'px';
        campo.style.overflowY = campo.scrollHeight > 150 ? 'auto' : 'hidden';
        enviar.disabled = !campo.value.trim() || form.dataset.submitting === 'true';
    };
    campo.addEventListener('input', ajustar);
    campo.addEventListener('keydown', event => {
        if (event.key !== 'Enter' || event.shiftKey || event.isComposing || !window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
        event.preventDefault();
        if (campo.value.trim() && form.dataset.submitting !== 'true') form.requestSubmit(enviar);
    });
    form.addEventListener('submit', event => {
        if (!campo.value.trim()) {
            event.preventDefault();
            campo.setCustomValidity('Escribe un mensaje antes de enviarlo.');
            campo.reportValidity();
        }
    }, { capture: true });
    window.addEventListener('pageshow', ajustar);
    ajustar();
});
