@props(['tratamentos'])
<section id="tratamentos" class="relative overflow-hidden bg-[#fbf7f1] py-24 lg:py-28">
    <x-ui.ramo tom="folha" :folhas="9" contorno class="pointer-events-none absolute top-10 -right-12 hidden h-72 w-auto rotate-[160deg] opacity-40 lg:block" />

    <div class="relative mx-auto max-w-7xl px-5 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-12 lg:items-end">
            <div class="lg:col-span-7">
                <p class="eyebrow flex items-center gap-3 text-terracota-escuro">
                    <span class="h-px w-8 bg-terracota"></span>
                    Tratamentos
                </p>
                <h2 class="mt-5 font-serif text-4xl leading-tight font-medium text-verde sm:text-5xl">
                    Cuidado para cada momento do <span class="text-terracota italic">seu sorriso</span>
                </h2>
            </div>
            <p class="text-base leading-relaxed text-texto/70 lg:col-span-5">
                Conheça os tratamentos realizados pela {{ $clinica['nome'] }}. A indicação de cada
                procedimento é feita individualmente, após avaliação clínica.
            </p>
        </div>

        <div class="mt-12 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
            @foreach ($tratamentos as $tratamento)
                <a href="{{ route('agendamento.create', ['tratamento' => $tratamento->slug]) }}"
                   class="group relative flex flex-col rounded-[1.5rem] border border-areia bg-creme p-4 sm:rounded-[1.75rem] sm:p-6 transition-all duration-300 hover:-translate-y-1 hover:border-terracota/40 hover:bg-white hover:shadow-[0_24px_40px_-28px_rgb(143_67_35/0.55)]">
                    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-verde text-creme transition-colors duration-300 group-hover:bg-terracota">
                        <x-ui.icon :name="$tratamento->icone" class="h-6 w-6" />
                    </span>
                    <h3 class="mt-4 font-serif text-lg leading-snug text-verde sm:mt-6 sm:text-xl">{{ $tratamento->nome }}</h3>
                    <p class="mt-2 hidden flex-1 text-sm leading-relaxed text-texto/65 sm:block">{{ $tratamento->descricao }}</p>
                    <span class="mt-auto inline-flex items-center gap-1.5 pt-4 text-sm font-semibold text-terracota-escuro sm:pt-5">
                        Agendar
                        <x-ui.icon name="arrow-right" class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                    </span>
                </a>
            @endforeach
        </div>

        <div class="mt-6 flex flex-col items-start gap-5 rounded-[1.75rem] bg-verde px-7 py-7 text-creme sm:flex-row sm:items-center sm:justify-between sm:px-10">
            <div class="flex items-center gap-4">
                <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-[#e8a07c]"><x-ui.icon name="lupa" class="h-6 w-6" /></span>
                <div>
                    <p class="font-serif text-xl">Não sabe qual tratamento precisa?</p>
                    <p class="mt-1 text-sm text-creme/70">Agende uma consulta de avaliação.</p>
                </div>
            </div>
            <a href="{{ route('agendamento.create', ['tratamento' => 'avaliacao']) }}" class="btn-primario shrink-0">
                Agendar avaliação
                <x-ui.icon name="arrow-right" class="h-4 w-4" />
            </a>
        </div>
    </div>
</section>
