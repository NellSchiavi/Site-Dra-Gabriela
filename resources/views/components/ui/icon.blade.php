@props(['name', 'class' => 'h-6 w-6'])

{{-- Ícones de traço fino centralizados num único componente. --}}
<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
     stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" {{ $attributes->merge(['class' => $class]) }}>
    @switch($name)
        {{-- Tratamentos --}}
        @case('dente')
            <path d="M7.6 3.6c-2.3 0-4.1 1.9-4.1 4.4 0 2.2.9 3.4 1.4 5.3.5 2 .7 7.2 2.6 7.2 1.6 0 1.6-5 4.5-5s2.9 5 4.5 5c1.9 0 2.1-5.2 2.6-7.2.5-1.9 1.4-3.1 1.4-5.3 0-2.5-1.8-4.4-4.1-4.4-1.8 0-2.8 1-4.4 1s-2.6-1-4.4-1Z" />
            @break
        @case('alinhador')
            <path d="M3.5 8.5c0 6.3 3.8 10.5 8.5 10.5s8.5-4.2 8.5-10.5" />
            <path d="M7 8.5c0 4.2 2.2 7 5 7s5-2.8 5-7" />
            <path d="M3.5 8.5H7M17 8.5h3.5M9.5 12.6h5" />
            @break
        @case('brilho')
            <path d="M11 4.5 12.6 9 17 10.5 12.6 12 11 16.5 9.4 12 5 10.5 9.4 9 11 4.5Z" />
            <path d="M18.5 3.5v3M17 5h3M17.5 16v4M15.5 18h4" />
            @break
        @case('sorriso')
            <circle cx="12" cy="12" r="8.5" />
            <path d="M7.8 13.6c1.1 1.7 2.5 2.5 4.2 2.5s3.1-.8 4.2-2.5M9.2 9.6h.01M14.8 9.6h.01" />
            @break
        @case('gota')
            <path d="M12 3.5s6 6.3 6 10.4a6 6 0 0 1-12 0c0-4.1 6-10.4 6-10.4Z" />
            <path d="M9.5 14.5a2.6 2.6 0 0 0 2.5 2.4" />
            @break
        @case('camadas')
            <path d="M12 4 3.5 8.4 12 12.8l8.5-4.4L12 4Z" />
            <path d="m3.5 12.5 8.5 4.4 8.5-4.4M3.5 16.4l8.5 4.1 8.5-4.1" />
            @break
        @case('extracao')
            <path d="M5.9 8.1c-1.8 0-3.2 1.5-3.2 3.4 0 1.7.7 2.6 1.1 4.1.4 1.6.5 5.6 2 5.6 1.2 0 1.2-3.9 3.5-3.9s2.3 3.9 3.5 3.9c1.5 0 1.6-4 2-5.6.4-1.5 1.1-2.4 1.1-4.1 0-1.9-1.4-3.4-3.2-3.4-1.4 0-2.2.8-3.4.8s-2-.8-3.4-.8Z" />
            <path d="M19 10.5V3M16.5 5.5 19 3l2.5 2.5" />
            @break
        @case('coroa')
            <path d="m4.5 9.5 2-4.5 3 3 2.5-4 2.5 4 3-3 2 4.5" />
            <path d="M4.5 9.5V12c0 1.4 1.1 2.5 2.5 2.5h10c1.4 0 2.5-1.1 2.5-2.5V9.5" />
            <path d="M9 14.5 9.8 20M15 14.5 14.2 20" />
            @break
        @case('crianca')
            <circle cx="12" cy="8.3" r="3.3" />
            <path d="M10.4 5.4c.6-1 2.3-1.3 3.3-.1M7.5 20.5v-2.2a4.5 4.5 0 0 1 9 0v2.2" />
            <path d="M18.5 4.5a1.6 1.6 0 1 0 0 3.2 1.6 1.6 0 0 0 0-3.2ZM18.5 7.7v3" />
            @break
        @case('adulto')
            <circle cx="12" cy="7.5" r="3.6" />
            <path d="M5 20.5v-.8a7 7 0 0 1 14 0v.8" />
            @break
        @case('lupa')
            <circle cx="10.8" cy="10.8" r="6.3" />
            <path d="m20 20-4.7-4.7M8.6 9.2a2.6 2.6 0 0 1 2.4-1.6" />
            @break

        {{-- Interface --}}
        @case('arrow-right')
            <path d="M4.5 12h15M13.5 5.5 19.5 12l-6 6.5" />
            @break
        @case('whatsapp')
            <path d="M7 18.2 4 20l1.9-3.4A7.9 7.9 0 1 1 12 20a7.9 7.9 0 0 1-5-1.8Z" />
            <path d="M9.2 9.3c.2-.5.4-.5.7-.5h.5c.2 0 .4 0 .6.4.2.5.6 1.4.6 1.5.1.1.1.3 0 .4-.1.2-.2.3-.3.4l-.4.4c-.1.1-.2.3-.1.5.3.5.8 1.1 1.4 1.6.6.5 1.2.8 1.7 1 .2.1.3 0 .4-.1l.5-.6c.1-.2.3-.2.5-.1l1.4.7c.2.1.3.2.4.3.1.2.1.9-.2 1.3-.3.4-1.1.9-1.9.9-.6 0-2.4-.3-4-1.8-1.9-1.8-2.6-3.6-2.7-3.9-.1-.3-.6-1.2-.6-2 0-.9.4-1.3.6-1.4Z" />
            @break
        @case('instagram')
            <rect x="4" y="4" width="16" height="16" rx="4.5" />
            <circle cx="12" cy="12" r="3.4" />
            <circle cx="16.6" cy="7.4" r="0.4" fill="currentColor" stroke="none" />
            @break
        @case('mail')
            <rect x="3.5" y="5.5" width="17" height="13" rx="2" />
            <path d="m4 7 8 6 8-6" />
            @break
        @case('map-pin')
            <path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11Z" />
            <circle cx="12" cy="10" r="2.4" />
            @break
        @case('calendar')
            <path d="M7 3.5V6M17 3.5V6M4 9h16M5 6h14a1 1 0 0 1 1 1v12a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1Z" />
            @break
        @case('clock')
            <circle cx="12" cy="12" r="8.5" />
            <path d="M12 7.5V12l3 2" />
            @break
        @case('check')
            <path d="m5 12.5 4.5 4.5L19 7.5" />
            @break
        @case('check-circle')
            <circle cx="12" cy="12" r="8.5" />
            <path d="m8.5 12.3 2.4 2.4 4.8-5" />
            @break
        @case('inbox')
            <path d="M4 13.5 6.2 5.8A1.5 1.5 0 0 1 7.6 4.7h8.8a1.5 1.5 0 0 1 1.4 1.1L20 13.5v4.8a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-4.8Z" />
            <path d="M4 13.5h4.5l1.2 2.2h4.6l1.2-2.2H20" />
            @break
        @case('heart')
            <path d="M12 19.5s-7.5-4.4-7.5-9.7A4.3 4.3 0 0 1 12 7.2a4.3 4.3 0 0 1 7.5 2.6c0 5.3-7.5 9.7-7.5 9.7Z" />
            @break
        @case('folha')
            <path d="M5 19c0-8 5-13.5 14-14 .5 9-5 14-13 14" />
            <path d="M5 19 13 11" />
            @break
        @case('chart-bar')
            <path d="M4 20V10M10 20V4M16 20v-7M22 20H2" />
            @break
        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h16" />
            @break
        @case('close')
            <path d="M6 6l12 12M18 6 6 18" />
            @break
        @case('logout')
            <path d="M9 4.5H6a1.5 1.5 0 0 0-1.5 1.5v12A1.5 1.5 0 0 0 6 19.5h3M15.5 16l4-4-4-4M19 12H9" />
            @break
        @case('external')
            <path d="M14 4.5h5.5V10M19.5 4.5 11 13M17 14v4.5a1 1 0 0 1-1 1H5.5a1 1 0 0 1-1-1V8a1 1 0 0 1 1-1H10" />
            @break
        @default
            <circle cx="12" cy="12" r="8.5" />
    @endswitch
</svg>
