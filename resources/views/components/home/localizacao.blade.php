<section id="localizacao" class="relative bg-creme py-24 lg:py-28">
    <div class="mx-auto max-w-7xl px-5 lg:px-8">
        <div class="grid gap-12 lg:grid-cols-12 lg:items-stretch">
            <div class="flex flex-col lg:col-span-5">
                <p class="eyebrow flex items-center gap-3 text-terracota-escuro">
                    <span class="h-px w-8 bg-terracota"></span>
                    Localização
                </p>
                <h2 class="mt-5 font-serif text-4xl leading-tight font-medium text-verde sm:text-5xl">
                    Onde <span class="text-terracota italic">atendemos</span>
                </h2>
                <p class="mt-5 max-w-md text-base leading-relaxed text-texto/70">
                    O consultório fica na Vila Colorado, em Suzano – SP. O atendimento é feito somente com hora marcada.
                </p>

                <div class="mt-10 space-y-4">
                    <div class="flex gap-4 rounded-[1.5rem] border border-areia bg-white/70 p-6">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-terracota/10 text-terracota-escuro"><x-ui.icon name="map-pin" class="h-5 w-5" /></span>
                        <div>
                            <p class="text-xs font-semibold tracking-wide text-texto/50 uppercase">Endereço</p>
                            <address class="mt-1 text-base leading-relaxed text-texto not-italic">
                                {{ $clinica['endereco']['logradouro'] }} – {{ $clinica['endereco']['bairro'] }}<br>
                                {{ $clinica['endereco']['cidade'] }} · CEP {{ $clinica['endereco']['cep'] }}
                            </address>
                            <a href="{{ $clinica['mapa_url'] }}" target="_blank" rel="noopener" class="link-arrow mt-3 text-terracota-escuro">
                                Como chegar
                                <x-ui.icon name="arrow-right" class="h-4 w-4" />
                            </a>
                        </div>
                    </div>

                    <div class="flex gap-4 rounded-[1.5rem] border border-areia bg-white/70 p-6">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-terracota/10 text-terracota-escuro"><x-ui.icon name="clock" class="h-5 w-5" /></span>
                        <div>
                            <p class="text-xs font-semibold tracking-wide text-texto/50 uppercase">Atendimento</p>
                            <p class="mt-1 text-base text-texto">{{ $clinica['atendimento'] }}</p>
                            <p class="mt-1 text-sm text-texto/60">{{ $clinica['atendimento_domingo'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative min-h-[22rem] lg:col-span-7">
                <div class="absolute inset-0 translate-x-3 translate-y-3 rounded-[2rem] bg-areia"></div>
                <iframe
                    src="{{ $clinica['mapa_embed'] }}"
                    title="Mapa com a localização do consultório da {{ $clinica['nome'] }}"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    class="relative h-full min-h-[22rem] w-full rounded-[2rem] border border-areia bg-areia grayscale-[35%] sepia-[15%]"
                ></iframe>
            </div>
        </div>
    </div>
</section>
