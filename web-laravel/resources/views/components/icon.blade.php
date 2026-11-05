@props(['name' => 'grid'])
@php
    $paths = [
        'home' => '<path d="m3 10 9-7 9 7v10H15v-7H9v7H3Z"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'box' => '<path d="m12 3 9 5-9 5-9-5Zm-9 5v10l9 5 9-5V8M12 13v10M7 5.8l10 5.5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'chart' => '<path d="M4 3v17h17M8 16v-5m5 5V6m5 10V9"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3m1-16a3 3 0 0 1 0 6m2 4a5 5 0 0 1 3 4v2"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="14" rx="2"/><path d="M8 7V4h8v3M3 12a21 21 0 0 0 18 0M12 12v4"/>',
        'shield' => '<path d="M12 3 3 7v5c0 5 9 10 9 10s9-5 9-10V7Zm-4 9 3 3 5-6"/>',
        'calculator' => '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M8 6h8M8 11h1m6 0h1m-8 4h1m6 0h1m-8 4h1m6 0h1"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'back' => '<path d="M19 12H5m6-6-6 6 6 6"/>',
        'search' => '<circle cx="10" cy="10" r="6"/><path d="m15 15 6 6"/>',
        'print' => '<path d="M7 8V3h10v5M7 17H3V9h18v8h-4M7 14h10v7H7Z"/>',
        'refresh' => '<path d="M20 7a9 9 0 1 0 1 9M20 3v5h-5"/>',
        'mountain' => '<path d="m2 20 7-13 4 7 3-11 6 17Zm4-7 3 1 2-2m3-2 2 1 2-1"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
    ];
@endphp
<svg {{ $attributes->class(['icon']) }} width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? $paths['grid'] !!}</svg>
