<div class="vlog-lightbox" data-vlog-lightbox hidden>
    <button type="button" class="vlog-lightbox-fondo" data-vlog-lightbox-cerrar aria-label="Cerrar vista"></button>
    <div class="vlog-lightbox-marco" role="dialog" aria-modal="true" aria-label="Vista de fotos del vlog">
        <button type="button" class="vlog-lightbox-cerrar" data-vlog-lightbox-cerrar aria-label="Cerrar">×</button>
        <button type="button" class="vlog-lightbox-nav prev" data-vlog-lightbox-prev aria-label="Foto anterior">←</button>
        <img data-vlog-lightbox-img alt="">
        <button type="button" class="vlog-lightbox-nav next" data-vlog-lightbox-next aria-label="Foto siguiente">→</button>
        <span class="vlog-lightbox-count" data-vlog-lightbox-count></span>
    </div>
</div>
@once
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/vlog-lightbox.css') }}?v={{ filemtime(public_path('css/vlog-lightbox.css')) }}" media="screen">
    @endpush
    @push('scripts')
        <script src="{{ asset('js/vlog-lightbox.js') }}?v={{ filemtime(public_path('js/vlog-lightbox.js')) }}" defer></script>
    @endpush
@endonce
