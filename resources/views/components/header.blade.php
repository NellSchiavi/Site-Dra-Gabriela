@php
    $naHome = request()->routeIs('home');
    $prefixo = $naHome ? '' : route('home');
    $links = [
        ['label' => 'Início', 'href' => $prefixo.'#inicio'],
        ['label' => 'Tratamentos', 'href' => $prefixo.'#tratamentos'],
        ['label' => 'Sobre', 'href' => $prefixo.'#sobre'],
        ['label' => 'Localização', 'href' => $prefixo.'#localizacao'],
        ['label' => 'Contato', 'href' => $prefixo.'#contato'],
    ];
@endphp
<header x-data="{ aberto: false, rolou: false }" @scroll.window="rolou = window.scrollY > 12"
        class="sticky top-0 z-30 transition-colors duration-300"
        :class="rolou || aberto ? 'bg-creme/95 shadow-[0_1px_0_rgb(43_36_32/0.08)] backdrop-blur' : 'bg-creme/0'">
    <div class="mx-auto flex h-20 max-w-7xl items-center justify-between gap-6 px-5 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="{{ $clinica['nome'] }} — página inicial">
            <x-ui.logo class="h-12 shrink-0" />
            <span class="leading-none">
                <span class="block font-serif text-lg font-medium text-verde sm:text-xl">Dra. Gabriela Ferri Paes</span>
                <span class="mt-1 block text-[0.62rem] font-semibold tracking-[0.18em] text-texto/60 uppercase">
                    {{ $clinica['profissao'] }}<span class="hidden min-[420px]:inline"> · {{ $clinica['cro'] }}</span>
                </span>
            </span>
        </a>

        <nav class="hidden items-center gap-8 lg:flex" aria-label="Navegação principal">
            @foreach ($links as $link)
                <a href="{{ $link['href'] }}" class="text-sm font-medium text-texto/75 transition-colors hover:text-terracota-escuro">{{ $link['label'] }}</a>
            @endforeach
        </nav>

        <div class="flex items-center gap-2">
            <a href="{{ route('agendamento.create') }}" class="btn-primario hidden !px-5 !py-2.5 sm:inline-flex">
                <x-ui.icon name="calendar" class="h-4 w-4" />
                Agende sua consulta
            </a>
            <button type="button" class="inline-flex items-center justify-center rounded-full border border-texto/15 p-2.5 text-texto lg:hidden"
                    @click="aberto = !aberto" :aria-expanded="aberto.toString()" aria-controls="menu-mobile">
                <span class="sr-only">Abrir menu</span>
                <x-ui.icon name="menu" x-show="!aberto" class="h-5 w-5" />
                <x-ui.icon name="close" x-show="aberto" x-cloak class="h-5 w-5" />
            </button>
        </div>
    </div>

    <nav id="menu-mobile" x-show="aberto" x-cloak x-transition.opacity @click.outside="aberto = false"
         class="border-t border-texto/10 bg-creme px-5 pt-2 pb-6 lg:hidden" aria-label="Navegação principal (celular)">
        @foreach ($links as $link)
            <a href="{{ $link['href'] }}" @click="aberto = false" class="block border-b border-texto/5 py-3 font-medium text-texto/80">{{ $link['label'] }}</a>
        @endforeach
        <a href="{{ route('agendamento.create') }}" class="btn-primario mt-5 w-full">Agende sua consulta</a>
    </nav>
</header>
