@push('styles')<link rel="stylesheet" href="{{ asset('css/chat.css') }}?v={{ filemtime(public_path('css/chat.css')) }}">@endpush
@push('scripts')<script src="{{ asset('js/chat.js') }}?v={{ filemtime(public_path('js/chat.js')) }}" defer></script>@endpush
