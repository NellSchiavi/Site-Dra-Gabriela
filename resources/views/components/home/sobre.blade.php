<section id="sobre" class="relative overflow-hidden bg-verde py-24 text-creme lg:py-32">
    <x-ui.ramo tom="folha" :folhas="14" contorno class="pointer-events-none absolute -bottom-20 -left-16 h-[30rem] w-auto -rotate-12 opacity-35" />
    <x-ui.ramo tom="folha" :folhas="9" contorno class="pointer-events-none absolute -top-16 right-6 h-72 w-auto rotate-[195deg] opacity-25" />

    <div class="relative mx-auto grid max-w-7xl items-center gap-16 px-5 lg:grid-cols-12 lg:px-8">
        {{-- Foto em moldura areia --}}
        <div class="lg:col-span-5">
            <div class="relative mx-auto max-w-sm lg:max-w-none">
                <div class="absolute -inset-3 -rotate-3 rounded-[2.25rem] bg-terracota/90"></div>
                <div class="relative overflow-hidden rounded-[2rem] bg-areia p-2.5">
                    <picture>
                        <source srcset="{{ asset('imagens/gabriela-sobre.webp') }}" type="image/webp">
                        <img src="{{ asset('imagens/gabriela-sobre.jpg') }}"
                             alt="{{ $clinica['nome'] }} sorrindo e segurando instrumentos odontológicos"
                             width="640" height="640" loading="lazy"
                             class="aspect-square w-full rounded-[1.6rem] object-cover">
                    </picture>
                </div>
                <x-ui.ramo tom="creme" :folhas="8" class="pointer-events-none absolute -right-10 -bottom-12 h-52 w-auto rotate-[28deg] opacity-90" />
            </div>
        </div>

        <div class="lg:col-span-7 lg:pl-6">
            <p class="eyebrow flex items-center gap-3 text-[#e8a07c]">
                <span class="h-px w-8 bg-[#e8a07c]"></span>
                Sobre a Dra. Gabriela
            </p>
            <h2 class="mt-5 font-serif text-4xl leading-tight font-medium sm:text-5xl">
                Cuidado, técnica e um olhar humano para o <span class="text-[#e8a07c] italic">seu sorriso.</span>
            </h2>

            {{-- Texto apenas com informações confirmadas. Espaço preparado para
                 uma apresentação pessoal escrita/aprovada pela própria Dra. --}}
            <div class="mt-7 max-w-2xl space-y-4 text-base leading-relaxed text-creme/80">
                <p>
                    A <strong class="font-semibold text-creme">{{ $clinica['nome'] }}</strong> é {{ mb_strtolower($clinica['profissao']) }},
                    registrada no {{ $clinica['cro'] }}, e atende adultos e crianças em Suzano – SP.
                </p>
                <p>
                    Entre os tratamentos realizados estão aparelho invisível, clareamento dental, estética,
                    lentes de resina, restaurações, raspagem, extrações e prótese.
                </p>
            </div>

            <dl class="mt-10 grid max-w-2xl grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ([
                    ['icone' => 'check-circle', 'titulo' => 'Registro profissional', 'texto' => $clinica['cro']],
                    ['icone' => 'heart', 'titulo' => 'Público atendido', 'texto' => 'Adultos e crianças'],
                    ['icone' => 'calendar', 'titulo' => 'Atendimento', 'texto' => 'Segunda a sábado'],
                    ['icone' => 'clock', 'titulo' => 'Consultas', 'texto' => 'Somente com hora marcada'],
                ] as $item)
                    <div class="flex items-center gap-4 rounded-2xl border border-white/10 bg-white/5 px-5 py-4">
                        <x-ui.icon :name="$item['icone']" class="h-6 w-6 shrink-0 text-[#e8a07c]" />
                        <div>
                            <dt class="text-xs tracking-wide text-creme/55 uppercase">{{ $item['titulo'] }}</dt>
                            <dd class="mt-0.5 font-medium">{{ $item['texto'] }}</dd>
                        </div>
                    </div>
                @endforeach
            </dl>

            <div class="mt-10 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('agendamento.create') }}" class="btn-primario">
                    Agende sua consulta
                    <x-ui.icon name="arrow-right" class="h-4 w-4" />
                </a>
                <a href="{{ $clinica['whatsapp_url'] }}" target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center gap-2 rounded-full border border-creme/30 px-6 py-3 text-sm font-semibold text-creme transition-colors hover:border-creme hover:bg-white/5">
                    <x-ui.icon name="whatsapp" class="h-4 w-4" />
                    Fale pelo WhatsApp
                </a>
            </div>
        </div>
    </div>
</section>
