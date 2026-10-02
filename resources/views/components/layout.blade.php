@props(['title' => null, 'description' => null])
@php
    $tituloFinal = $title ?? 'Dra. Gabriela Ferri Paes | Cirurgiã-Dentista em Suzano - SP';
    $descricaoFinal = $description ?? 'Dra. Gabriela Ferri Paes, cirurgiã-dentista (CRO-SP 113798) em Suzano - SP. Atendimento adulto e infantil, somente com hora marcada.';
@endphp
<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $descricaoFinal }}">
    {{-- Demo temporária: não indexar (não é mecanismo de segurança). --}}
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#1E3A2C">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <title>{{ $tituloFinal }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col font-sans">
    <a href="#conteudo-principal" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-md focus:bg-verde focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        Pular para o conteúdo
    </a>

    <x-header />

    <main id="conteudo-principal" class="flex-1">
        {{ $slot }}
    </main>

    <x-footer />

    {{-- WhatsApp flutuante --}}
    <a href="{{ $clinica['whatsapp_url'] }}" target="_blank" rel="noopener"
       class="group fixed right-4 bottom-4 z-40 flex items-center gap-2 rounded-full bg-verde py-3 pr-3 pl-3 text-white shadow-[0_14px_30px_-12px_rgb(21_42_31/0.8)] ring-1 ring-white/10 transition-all hover:bg-verde-escuro sm:right-6 sm:bottom-6 sm:pr-5">
        <x-ui.icon name="whatsapp" class="h-6 w-6" />
        <span class="hidden text-sm font-semibold sm:inline">Fale pelo WhatsApp</span>
        <span class="sr-only sm:hidden">Fale pelo WhatsApp</span>
    </a>
</body>
</html>
