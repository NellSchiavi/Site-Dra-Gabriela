<section id="inicio" class="relative -mt-20 overflow-hidden pt-20">
    {{-- Fundo: textura leve no creme + painel verde integrado à direita (desktop) --}}
    <div class="textura pointer-events-none absolute inset-0 opacity-60"></div>
    <div class="pointer-events-none absolute top-20 right-0 bottom-0 hidden w-[38%] rounded-tl-[2.5rem] rounded-bl-[7rem] bg-verde lg:block">
        <div class="textura absolute inset-0 rounded-tl-[2.5rem] rounded-bl-[7rem] opacity-[0.07]"></div>
        <x-ui.ramo tom="creme" :folhas="12" contorno class="absolute top-24 right-6 h-80 w-auto rotate-[200deg] opacity-25" />
        <x-ui.ramo tom="folha" :folhas="10" contorno class="absolute -right-8 bottom-6 h-72 w-auto -rotate-[10deg] opacity-40" />
    </div>

    <div class="relative mx-auto grid max-w-7xl items-center gap-12 px-5 pt-6 pb-16 sm:pt-10 md:grid-cols-12 md:gap-8 lg:min-h-[calc(100svh-5rem)] lg:gap-6 lg:px-8 lg:py-12">
        {{-- Texto --}}
        <div class="md:col-span-7 lg:col-span-6 lg:pr-4">
            <p class="eyebrow flex items-center gap-3 text-terracota-escuro">
                <span class="h-px w-10 bg-terracota"></span>
                Odontologia para adultos e crianças
            </p>

            <h1 class="mt-6 font-serif text-[2.5rem] leading-[1.02] font-medium tracking-tight text-verde min-[400px]:text-[2.8rem] sm:text-6xl md:text-5xl lg:text-6xl xl:text-[4.6rem]">
                Seu sorriso,<br class="hidden sm:block">
                cuidado com
                <span class="block text-terracota italic">naturalidade<br class="hidden sm:block"> e confiança.</span>
            </h1>

            <p class="mt-7 max-w-md border-l-2 border-terracota/40 pl-5 text-lg leading-relaxed text-texto/75">
                Atendimento odontológico com a {{ $clinica['nome'] }}, {{ mb_strtolower($clinica['profissao']) }},
                em Suzano – SP. Consultas somente com hora marcada.
            </p>

            <div class="mt-9 flex flex-col gap-3 sm:flex-row sm:items-center md:flex-col md:items-start xl:flex-row xl:items-center">
                <a href="{{ route('agendamento.create') }}" class="btn-primario !px-8 !py-4 text-base">
                    Agende sua consulta
                    <x-ui.icon name="arrow-right" class="h-4 w-4" />
                </a>
                <a href="#tratamentos" class="btn-contorno !px-7 !py-4 text-base">
                    Conheça os tratamentos
                </a>
            </div>

            <ul class="mt-10 grid max-w-xl grid-cols-1 gap-4 border-t border-texto/10 pt-7 text-sm text-texto/75 sm:grid-cols-3 md:grid-cols-1 xl:grid-cols-3">
                <li class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-terracota/10 text-terracota-escuro"><x-ui.icon name="heart" class="h-5 w-5" /></span>
                    Adultos e crianças
                </li>
                <li class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-terracota/10 text-terracota-escuro"><x-ui.icon name="calendar" class="h-5 w-5" /></span>
                    Somente com hora marcada
                </li>
                <li class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-terracota/10 text-terracota-escuro"><x-ui.icon name="map-pin" class="h-5 w-5" /></span>
                    Vila Colorado, Suzano
                </li>
            </ul>
        </div>

        {{-- Foto: arco grande atravessando a divisa creme/verde --}}
        <div class="relative md:col-span-5 lg:col-span-6">
            <div class="relative mx-auto w-full max-w-[22rem] sm:max-w-[26rem] lg:mr-0 lg:ml-auto lg:max-w-[31rem]">
                {{-- Bloco verde atrás da foto só no celular/tablet (no desktop o painel cumpre esse papel) --}}
                <div class="absolute inset-0 translate-x-4 translate-y-4 rounded-t-full rounded-b-[2rem] bg-verde sm:translate-x-6 sm:translate-y-6 lg:hidden"></div>
                {{-- Contorno terracota em arco, deslocado — reforça a moldura --}}
                <div class="absolute -inset-3 -translate-x-3 translate-y-3 rounded-t-full rounded-b-[2.4rem] border border-terracota/60 sm:-inset-4 sm:-translate-x-5 sm:translate-y-5"></div>

                <x-ui.ramo tom="verde" :folhas="13" class="pointer-events-none absolute -bottom-10 -left-16 z-20 h-72 w-auto -rotate-[18deg] sm:-left-24 sm:h-96 md:-left-12 md:h-72 lg:-left-28 lg:h-[27rem]" />
                <x-ui.ramo tom="terracota" :folhas="7" class="pointer-events-none absolute -top-8 -right-6 z-20 h-40 w-auto rotate-[28deg] opacity-90 sm:h-48 lg:-right-10" />

                <div class="relative z-10 aspect-[4/5] overflow-hidden rounded-t-full rounded-b-[2rem] bg-areia shadow-[0_40px_70px_-35px_rgb(21_42_31/0.75)] ring-[6px] ring-creme sm:ring-8">
                    <picture>
                        <source srcset="{{ asset('imagens/gabriela-hero.webp') }}" type="image/webp">
                        <img src="{{ asset('imagens/gabriela-hero.jpg') }}"
                             alt="Retrato da {{ $clinica['nome'] }}, sorrindo, de blazer preto"
                             width="1080" height="1080" fetchpriority="high"
                             class="h-full w-full object-cover object-[50%_18%]">
                    </picture>
                </div>

                <div class="absolute -bottom-7 left-1/2 z-30 w-[88%] -translate-x-1/2 rounded-2xl bg-creme/95 px-5 py-4 shadow-[0_18px_40px_-20px_rgb(43_36_32/0.5)] ring-1 ring-areia backdrop-blur sm:left-auto sm:-right-6 sm:w-auto sm:translate-x-0 md:-right-3 lg:-left-6 lg:right-auto xl:-left-14">
                    <div class="flex items-center gap-3">
                        <x-ui.logo class="h-11 shrink-0" />
                        <div class="leading-tight">
                            <p class="font-serif text-base text-verde">{{ $clinica['nome'] }}</p>
                            <p class="mt-0.5 text-xs text-texto/60">{{ $clinica['profissao'] }} · {{ $clinica['cro'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
