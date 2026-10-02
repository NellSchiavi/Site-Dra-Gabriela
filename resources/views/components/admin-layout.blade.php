@props(['title' => null])
@php
    $itensSidebar = [
        ['label' => 'Dashboard', 'icone' => 'chart-bar', 'rota' => 'admin.dashboard'],
        ['label' => 'Agenda', 'icone' => 'calendar', 'rota' => 'admin.agenda'],
        ['label' => 'Disponibilidade', 'icone' => 'clock', 'rota' => 'admin.disponibilidade.index'],
    ];
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#1E3A2C">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">

    <title>{{ $title ?? 'Painel' }} — Painel — {{ $clinica['nome'] }}</title>

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-creme font-sans text-texto antialiased" x-data="{ sidebarAberta: false }">
    <a href="#conteudo-painel" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-md focus:bg-verde focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white">
        Pular para o conteúdo
    </a>

    <div class="flex min-h-screen">
        <div x-show="sidebarAberta" x-cloak x-transition.opacity @click="sidebarAberta = false"
             class="fixed inset-0 z-30 bg-texto/50 lg:hidden" aria-hidden="true"></div>

        <aside id="admin-sidebar"
               :class="sidebarAberta ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 flex-col overflow-hidden bg-verde text-creme transition-transform duration-200 lg:static lg:translate-x-0"
               aria-label="Navegação do painel administrativo">
            <x-ui.ramo tom="folha" :folhas="11" contorno class="pointer-events-none absolute -bottom-16 -left-10 h-72 w-auto -rotate-12 opacity-30" />

            <div class="relative flex h-16 items-center gap-2.5 border-b border-white/10 px-6">
                <x-ui.logo sobre-escuro class="h-8" />
                <span class="font-serif text-base">Painel</span>
            </div>

            <nav class="relative flex-1 space-y-1 px-3 py-4" aria-label="Seções do painel">
                @foreach ($itensSidebar as $item)
                    <a href="{{ route($item['rota']) }}"
                       @if (request()->routeIs($item['rota'])) aria-current="page" @endif
                       class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-colors {{ request()->routeIs($item['rota']) ? 'bg-white/10 text-white' : 'text-creme/75 hover:bg-white/5 hover:text-white' }}">
                        <x-ui.icon :name="$item['icone']" class="h-5 w-5 shrink-0" />
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="relative space-y-3 border-t border-white/10 px-6 py-5 text-xs text-creme/60">
                <a href="{{ route('home') }}" target="_blank" class="flex items-center gap-2 hover:text-white">
                    <x-ui.icon name="external" class="h-4 w-4" /> Ver site
                </a>
            </div>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-areia bg-white/90 px-4 backdrop-blur lg:px-8">
                <div class="flex items-center gap-3">
                    <button type="button"
                            class="inline-flex items-center justify-center rounded-full border border-areia p-2 text-texto lg:hidden"
                            @click="sidebarAberta = !sidebarAberta"
                            :aria-expanded="sidebarAberta.toString()"
                            aria-controls="admin-sidebar">
                        <span class="sr-only">Abrir menu do painel</span>
                        <x-ui.icon name="menu" x-show="!sidebarAberta" class="h-5 w-5" />
                        <x-ui.icon name="close" x-show="sidebarAberta" x-cloak class="h-5 w-5" />
                    </button>

                    <div class="leading-tight">
                        <p class="font-serif text-sm text-verde sm:text-base">{{ $clinica['nome'] }}</p>
                        <p class="text-xs text-texto/55">Painel administrativo</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-2 rounded-full border border-areia px-4 py-2 text-sm font-semibold text-texto transition-colors hover:border-terracota hover:bg-areia/30">
                        <x-ui.icon name="logout" class="h-4 w-4" />
                        Sair
                    </button>
                </form>
            </header>

            <main id="conteudo-painel" class="flex-1 px-4 py-8 lg:px-8">
                {{ $slot }}
            </main>

            <x-admin.rodape class="border-t border-areia px-4 py-5 lg:px-8" />

        </div>
    </div>
</body>
</html>
