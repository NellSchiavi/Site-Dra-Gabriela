<section id="contato" class="bg-creme pb-24 lg:pb-28">
    <div class="mx-auto max-w-7xl px-5 lg:px-8">
        <div class="relative overflow-hidden rounded-[2.5rem] bg-terracota px-7 py-14 text-white sm:px-12 lg:px-16 lg:py-20">
            <x-ui.ramo tom="creme" :folhas="12" contorno class="pointer-events-none absolute -right-8 -bottom-24 h-[26rem] w-auto rotate-[15deg] opacity-30" />
            <div class="pointer-events-none absolute -top-24 -left-24 h-72 w-72 rounded-full bg-terracota-escuro/40"></div>

            <div class="relative grid gap-12 lg:grid-cols-12 lg:items-center">
                <div class="lg:col-span-7">
                    <p class="eyebrow text-white/75">Contato</p>
                    <h2 class="mt-4 font-serif text-4xl leading-tight font-medium sm:text-5xl">
                        Agende sua consulta
                    </h2>
                    <p class="mt-5 max-w-lg text-lg leading-relaxed text-white/85">
                        Escolha o tratamento, a data e o horário disponível. Depois do envio, sua
                        solicitação é analisada e confirmada.
                    </p>
                    <div class="mt-9 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ route('agendamento.create') }}" class="inline-flex items-center justify-center gap-2 rounded-full bg-creme px-7 py-3.5 text-base font-semibold text-terracota-escuro transition-all hover:-translate-y-0.5 hover:bg-white">
                            Agende sua consulta
                            <x-ui.icon name="arrow-right" class="h-4 w-4" />
                        </a>
                        <a href="{{ $clinica['whatsapp_url'] }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center gap-2 rounded-full border border-white/50 px-7 py-3.5 text-base font-semibold text-white transition-colors hover:border-white hover:bg-white/10">
                            <x-ui.icon name="whatsapp" class="h-5 w-5" />
                            Fale pelo WhatsApp
                        </a>
                    </div>
                </div>

                <ul class="space-y-3 lg:col-span-5">
                    <li>
                        <a href="{{ $clinica['whatsapp_url'] }}" target="_blank" rel="noopener" class="flex items-center gap-4 rounded-2xl bg-white/10 px-5 py-4 ring-1 ring-white/15 transition-colors hover:bg-white/15">
                            <x-ui.icon name="whatsapp" class="h-6 w-6 shrink-0" />
                            <span><span class="block text-xs tracking-wide text-white/65 uppercase">WhatsApp</span><span class="font-medium">{{ $clinica['whatsapp_exibicao'] }}</span></span>
                        </a>
                    </li>
                    <li>
                        <a href="mailto:{{ $clinica['email'] }}" class="flex items-center gap-4 rounded-2xl bg-white/10 px-5 py-4 ring-1 ring-white/15 transition-colors hover:bg-white/15">
                            <x-ui.icon name="mail" class="h-6 w-6 shrink-0" />
                            <span class="min-w-0"><span class="block text-xs tracking-wide text-white/65 uppercase">E-mail</span><span class="block font-medium break-all">{{ $clinica['email'] }}</span></span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ $clinica['instagram_url'] }}" target="_blank" rel="noopener" class="flex items-center gap-4 rounded-2xl bg-white/10 px-5 py-4 ring-1 ring-white/15 transition-colors hover:bg-white/15">
                            <x-ui.icon name="instagram" class="h-6 w-6 shrink-0" />
                            <span><span class="block text-xs tracking-wide text-white/65 uppercase">Instagram</span><span class="font-medium">{{ $clinica['instagram_usuario'] }}</span></span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</section>
