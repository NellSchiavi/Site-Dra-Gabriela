@php($prefixo = request()->routeIs('home') ? '' : route('home'))
<footer class="relative overflow-hidden bg-verde text-creme">
    <x-ui.ramo tom="folha" :folhas="13" contorno class="pointer-events-none absolute -bottom-16 -left-10 h-80 w-auto -rotate-12 opacity-30" />
    <x-ui.ramo tom="folha" :folhas="9" contorno class="pointer-events-none absolute -top-10 -right-6 h-64 w-auto rotate-[200deg] opacity-25" />

    <div class="relative mx-auto max-w-7xl px-5 pt-16 pb-8 lg:px-8">
        <div class="grid gap-12 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <div class="flex items-center gap-3">
                    <x-ui.logo sobre-escuro class="h-11" />
                    <div>
                        <p class="font-serif text-xl">{{ $clinica['nome'] }}</p>
                        <p class="mt-1 text-[0.65rem] font-semibold tracking-[0.18em] text-creme/60 uppercase">{{ $clinica['profissao'] }} · {{ $clinica['cro'] }}</p>
                    </div>
                </div>
                <p class="mt-6 max-w-xs font-serif text-2xl leading-snug text-creme/90 italic">
                    Seu sorriso, cuidado com naturalidade e confiança.
                </p>
            </div>

            <div class="lg:col-span-2">
                <p class="eyebrow text-[#d88a63]">Navegue</p>
                <ul class="mt-4 space-y-2.5 text-sm text-creme/75">
                    <li><a href="{{ $prefixo }}#tratamentos" class="hover:text-white">Tratamentos</a></li>
                    <li><a href="{{ $prefixo }}#sobre" class="hover:text-white">Sobre a Dra.</a></li>
                    <li><a href="{{ $prefixo }}#localizacao" class="hover:text-white">Localização</a></li>
                    <li><a href="{{ route('agendamento.create') }}" class="hover:text-white">Agendamento</a></li>
                </ul>
            </div>

            <div class="lg:col-span-3">
                <p class="eyebrow text-[#d88a63]">Contato</p>
                <ul class="mt-4 space-y-3 text-sm text-creme/75">
                    <li><a href="{{ $clinica['whatsapp_url'] }}" target="_blank" rel="noopener" class="flex items-center gap-2.5 hover:text-white"><x-ui.icon name="whatsapp" class="h-4 w-4 shrink-0" />{{ $clinica['whatsapp_exibicao'] }}</a></li>
                    <li><a href="mailto:{{ $clinica['email'] }}" class="flex items-center gap-2.5 break-all hover:text-white"><x-ui.icon name="mail" class="h-4 w-4 shrink-0" />{{ $clinica['email'] }}</a></li>
                    <li><a href="{{ $clinica['instagram_url'] }}" target="_blank" rel="noopener" class="flex items-center gap-2.5 hover:text-white"><x-ui.icon name="instagram" class="h-4 w-4 shrink-0" />{{ $clinica['instagram_usuario'] }}</a></li>
                </ul>
            </div>

            <div class="lg:col-span-3">
                <p class="eyebrow text-[#d88a63]">Endereço</p>
                <address class="mt-4 text-sm leading-relaxed text-creme/75 not-italic">
                    {{ $clinica['endereco']['logradouro'] }}<br>
                    {{ $clinica['endereco']['bairro'] }} · {{ $clinica['endereco']['cidade'] }}<br>
                    CEP {{ $clinica['endereco']['cep'] }}
                </address>
                <p class="mt-4 text-sm text-creme/75">{{ $clinica['atendimento'] }}</p>
            </div>
        </div>

        <div class="mt-14 flex flex-col gap-4 border-t border-white/10 pt-6 pb-20 text-xs text-creme/50 md:flex-row md:items-end md:justify-between md:pb-0 lg:pr-56">
            <div class="space-y-1.5">
                <p>© {{ now()->year }} NS | Nelson Schiavi Soluções Digitais. Todos os direitos reservados.</p>
            </div>

            <div class="flex flex-wrap items-center gap-x-5 gap-y-2">
                @if ($clinica['demo'])
                    <p class="inline-flex items-center gap-1.5">
                        <span class="h-1.5 w-1.5 rounded-full bg-[#d88a63]/70"></span>
                        Ambiente de demonstração
                    </p>
                    <span class="text-creme/30" aria-hidden="true">·</span>
                @endif
                {{-- Acesso discreto ao painel (fora do menu principal). --}}
                <a href="{{ url('/admin') }}" rel="nofollow" class="inline-flex items-center gap-1.5 text-creme/45 underline-offset-4 transition-colors hover:text-creme/80 hover:underline">
                    <x-ui.icon name="logout" class="h-3.5 w-3.5 rotate-180" />
                    Área administrativa
                </a>
            </div>
        </div>
    </div>
</footer>
