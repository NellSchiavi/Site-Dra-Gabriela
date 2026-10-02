@php
    $rotulosPasso = ['Tratamento', 'Data', 'Horário', 'Seus dados', 'Revisão'];
@endphp

<x-layout title="Agende sua consulta — Dra. Gabriela Ferri Paes">
    <section
        x-data="agendamentoWizard({
            tratamentos: {{ Js::from($tratamentos->map(fn ($t) => ['id' => $t->id, 'nome' => $t->nome, 'icone' => $t->icone, 'descricao' => $t->descricao])) }},
            preSelecionado: {{ Js::from($tratamentoPreSelecionado) }},
            hoje: {{ Js::from(now()->toDateString()) }},
            old: {{ Js::from(old()) }},
            erros: {{ Js::from($errors->toArray()) }},
        })"
        x-cloak
        class="relative overflow-hidden px-5 pt-10 pb-24 lg:px-8 lg:pt-14"
    >
        <div class="textura pointer-events-none absolute inset-0 opacity-50"></div>
        <x-ui.ramo tom="folha" :folhas="10" contorno class="pointer-events-none absolute top-20 -left-16 hidden h-80 w-auto -rotate-12 opacity-40 lg:block" />
        <x-ui.ramo tom="terracota" :folhas="8" contorno class="pointer-events-none absolute top-40 -right-10 hidden h-64 w-auto rotate-[160deg] opacity-40 lg:block" />

        <div class="relative mx-auto max-w-3xl">
            <p class="eyebrow text-center text-terracota-escuro">Agendamento online</p>
            <h1 class="mt-4 text-center font-serif text-4xl font-medium text-verde sm:text-5xl">
                Agende sua <span class="text-terracota italic">consulta</span>
            </h1>
            <p class="mx-auto mt-4 max-w-md text-center text-base text-texto/70">
                Escolha o tratamento, a data e um horário disponível. Sua solicitação será confirmada em seguida.
            </p>

            <p class="sr-only" role="status" aria-live="polite"
               x-text="'Etapa ' + step + ' de 5: ' + {{ Js::from($rotulosPasso) }}[step - 1]"></p>

            {{-- Indicador de progresso --}}
            <ol class="mt-12 flex items-center justify-between" aria-label="Etapas do agendamento">
                @foreach ($rotulosPasso as $indice => $rotulo)
                    @php($numero = $indice + 1)
                    <li class="flex flex-1 flex-col items-center gap-2 {{ $indice > 0 ? 'relative before:absolute before:right-1/2 before:top-4 before:h-px before:w-full before:bg-areia sm:before:top-[1.125rem]' : '' }}">
                        <button
                            type="button"
                            @click="irParaStep({{ $numero }})"
                            :disabled="{{ $numero }} > maxStepAlcancado"
                            :aria-current="step === {{ $numero }} ? 'step' : null"
                            :aria-label="'{{ $rotulo }}' + (step === {{ $numero }} ? ' (etapa atual)' : '')"
                            class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border text-sm font-semibold transition-colors sm:h-9 sm:w-9"
                            :class="step === {{ $numero }}
                                ? 'border-terracota bg-terracota text-white'
                                : ({{ $numero }} <= maxStepAlcancado ? 'border-verde bg-creme text-verde' : 'border-areia bg-creme text-texto/30 cursor-not-allowed')"
                        >{{ $numero }}</button>
                        <span class="hidden text-[0.65rem] font-semibold tracking-wide text-texto/55 uppercase sm:block">{{ $rotulo }}</span>
                    </li>
                @endforeach
            </ol>

            @if ($errors->any())
                <div class="mt-8 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800" role="alert">
                    <ul class="list-disc space-y-1 pl-4">
                        @foreach ($errors->all() as $mensagemErro)
                            <li>{{ $mensagemErro }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form x-ref="form" method="POST" action="{{ route('agendamento.store') }}" @submit.prevent="onSubmit" class="mt-10" novalidate>
                @csrf
                <input type="hidden" name="tratamento_id" :value="tratamentoId">
                <input type="hidden" name="data" :value="dataSelecionada">
                <input type="hidden" name="hora_inicio" :value="horaSelecionada">

                {{-- PASSO 1 — Tratamento --}}
                <div x-show="step === 1" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                    <h2 class="mb-5 text-center font-serif text-xl text-verde">Qual tratamento você procura?</h2>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach ($tratamentos as $tratamento)
                            <button
                                type="button"
                                @click="escolherTratamento({{ $tratamento->id }})"
                                :aria-pressed="tratamentoId === {{ $tratamento->id }}"
                                class="group flex items-center gap-4 rounded-2xl border bg-white/80 p-4 text-left transition-all hover:-translate-y-0.5 {{ $tratamento->slug === 'avaliacao' ? 'sm:col-span-2' : '' }}"
                                :class="tratamentoId === {{ $tratamento->id }} ? 'border-terracota ring-1 ring-terracota' : 'border-areia hover:border-terracota/50'"
                            >
                                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition-colors {{ $tratamento->slug === 'avaliacao' ? 'bg-terracota text-white' : 'bg-verde text-creme group-hover:bg-terracota' }}">
                                    <x-ui.icon :name="$tratamento->icone" class="h-5 w-5" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block font-serif text-base text-verde">{{ $tratamento->nome }}</span>
                                    <span class="mt-0.5 block text-xs leading-snug text-texto/55">{{ $tratamento->descricao }}</span>
                                </span>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- PASSO 2 — Data --}}
                <div x-show="step === 2" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                    <h2 class="sr-only">Escolha a data</h2>
                    <div class="rounded-[1.75rem] border border-areia bg-white p-5 shadow-[0_24px_50px_-40px_rgb(43_36_32/0.6)] sm:p-8">
                        <p class="mb-5 flex items-center justify-center gap-2 text-sm text-texto/60" x-show="tratamentoSelecionado">
                            <x-ui.icon name="check-circle" class="h-4 w-4 text-terracota" />
                            <span x-text="tratamentoSelecionado ? tratamentoSelecionado.nome : ''"></span>
                        </p>
                        <div class="flex items-center justify-between">
                            <button type="button" @click="mesAnterior" :disabled="!podeVoltarMes"
                                    aria-label="Mês anterior"
                                    class="flex h-10 w-10 items-center justify-center rounded-full border border-areia text-texto transition-colors hover:border-terracota disabled:cursor-not-allowed disabled:opacity-30">
                                <x-ui.icon name="arrow-right" class="h-4 w-4 rotate-180" />
                            </button>
                            <p class="font-serif text-xl text-verde first-letter:uppercase" x-text="mesAtualFormatado"></p>
                            <button type="button" @click="proximoMes" aria-label="Próximo mês"
                                    class="flex h-10 w-10 items-center justify-center rounded-full border border-areia text-texto transition-colors hover:border-terracota">
                                <x-ui.icon name="arrow-right" class="h-4 w-4" />
                            </button>
                        </div>

                        <p x-show="carregandoMes" x-cloak class="mt-6 text-center text-sm text-texto/60">Carregando disponibilidade…</p>

                        <div x-show="!carregandoMes && erroMes" x-cloak class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-4 text-center" role="alert">
                            <p class="text-sm text-red-800" x-text="erroMes"></p>
                            <button type="button" @click="carregarMes" class="btn-verde mt-3">Tentar novamente</button>
                        </div>

                        <div x-show="!carregandoMes && !erroMes" x-cloak>
                            <div class="mt-6 grid grid-cols-7 gap-1 text-center text-xs font-semibold tracking-wide text-texto/45 uppercase sm:gap-2">
                                <template x-for="d in diasSemana" :key="d">
                                    <span x-text="d"></span>
                                </template>
                            </div>

                            <div class="relative mt-2 grid grid-cols-7 gap-1 sm:gap-2" role="grid" aria-label="Calendário de datas disponíveis">
                                <template x-for="(dia, indice) in diasDoMes" :key="indice">
                                    <div class="flex items-center justify-center">
                                        <button
                                            type="button"
                                            x-show="dia"
                                            @click="dia && escolherData(dia)"
                                            :disabled="!dia || dia.passada || !dia.disponivel"
                                            :aria-label="dia ? (dia.dia + (dia.disponivel && !dia.passada ? ' — disponível' : ' — indisponível')) : ''"
                                            :aria-pressed="dia && dataSelecionada === dia.data"
                                            class="flex h-10 w-10 items-center justify-center rounded-full border text-sm font-medium transition-colors sm:h-12 sm:w-12"
                                            :class="{
                                                'border-terracota bg-terracota text-white': dia && dataSelecionada === dia.data,
                                                'border-verde/25 bg-verde/5 text-verde hover:border-terracota hover:bg-terracota/10 cursor-pointer': dia && !dia.passada && dia.disponivel && dataSelecionada !== dia.data,
                                                'border-transparent text-texto/25 cursor-not-allowed': dia && (dia.passada || !dia.disponivel),
                                                'ring-1 ring-terracota/60': dia && dia.hoje && dataSelecionada !== dia.data,
                                            }"
                                        ><span x-text="dia ? dia.dia : ''"></span></button>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-6 flex items-center justify-center gap-5 text-xs text-texto/55">
                                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full border border-verde/30 bg-verde/10"></span> Disponível</span>
                                <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-areia"></span> Indisponível</span>
                            </div>
                        </div>
                    </div>

                    <button type="button" @click="irParaStep(1)" class="link-arrow mt-6 text-terracota-escuro">
                        <x-ui.icon name="arrow-right" class="h-4 w-4 rotate-180" />
                        Trocar o tratamento
                    </button>
                </div>

                {{-- PASSO 3 — Horário --}}
                <div x-show="step === 3" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                    <h2 class="sr-only">Escolha o horário</h2>
                    <div class="rounded-[1.75rem] border border-areia bg-white p-5 shadow-[0_24px_50px_-40px_rgb(43_36_32/0.6)] sm:p-8">
                        <p class="font-serif text-xl text-verde" x-text="dataSelecionadaFormatada"></p>
                        <p class="mt-1 text-sm text-texto/55">Escolha um dos horários disponíveis:</p>

                        <p x-show="carregandoHorarios" x-cloak class="mt-4 text-sm text-texto/60">Carregando horários…</p>

                        <div x-show="!carregandoHorarios && erroHorarios" x-cloak class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-4 text-center" role="alert">
                            <p class="text-sm text-red-800" x-text="erroHorarios"></p>
                            <button type="button" @click="carregarHorarios" class="btn-verde mt-3">Tentar novamente</button>
                        </div>

                        <div x-show="!carregandoHorarios && !erroHorarios && horarios.length > 0" x-cloak class="mt-6 grid grid-cols-3 gap-3 sm:grid-cols-4">
                            <template x-for="horario in horarios" :key="horario.inicio">
                                <button
                                    type="button"
                                    @click="escolherHorario(horario)"
                                    :aria-pressed="horaSelecionada === horario.inicio"
                                    class="rounded-xl border py-3 text-sm font-semibold transition-colors"
                                    :class="horaSelecionada === horario.inicio ? 'border-terracota bg-terracota text-white' : 'border-verde/20 bg-verde/5 text-verde hover:border-terracota hover:bg-terracota/10'"
                                    x-text="horario.inicio"
                                ></button>
                            </template>
                        </div>

                        <div x-show="!carregandoHorarios && buscouHorarios && horarios.length === 0" x-cloak class="mt-5">
                            <p class="text-sm text-texto/70">Não há horários disponíveis nesta data.</p>
                            <button type="button" @click="irParaStep(2)" class="link-arrow mt-3 text-terracota-escuro">
                                Escolher outra data
                                <x-ui.icon name="arrow-right" class="h-4 w-4" />
                            </button>
                        </div>

                        @error('hora_inicio')
                            <p class="mt-4 text-sm font-medium text-red-700" role="alert">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="button" @click="irParaStep(2)" class="link-arrow mt-6 text-terracota-escuro">
                        <x-ui.icon name="arrow-right" class="h-4 w-4 rotate-180" />
                        Escolher outra data
                    </button>
                </div>

                {{-- PASSO 4 — Dados do paciente --}}
                <div x-show="step === 4" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                    <h2 class="sr-only">Seus dados</h2>
                    <div class="space-y-5 rounded-[1.75rem] border border-areia bg-white p-5 shadow-[0_24px_50px_-40px_rgb(43_36_32/0.6)] sm:p-8">
                        <div>
                            <label for="nome_cliente" class="text-sm font-medium text-texto">Nome completo</label>
                            <input type="text" id="nome_cliente" name="nome_cliente" x-model="form.nome_cliente" autocomplete="name" maxlength="150"
                                   aria-required="true" @if ($errors->has('nome_cliente')) aria-invalid="true" aria-describedby="erro-nome" @endif
                                   class="campo mt-1.5">
                            @error('nome_cliente')
                                <p id="erro-nome" class="mt-1.5 text-xs font-medium text-red-700" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                            <div>
                                <label for="telefone_cliente" class="text-sm font-medium text-texto">WhatsApp</label>
                                <input type="tel" id="telefone_cliente" name="telefone_cliente" x-model="form.telefone_cliente" autocomplete="tel" maxlength="30"
                                       placeholder="(11) 90000-0000"
                                       aria-required="true" @if ($errors->has('telefone_cliente')) aria-invalid="true" aria-describedby="erro-telefone" @endif
                                       class="campo mt-1.5">
                                @error('telefone_cliente')
                                    <p id="erro-telefone" class="mt-1.5 text-xs font-medium text-red-700" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="email_cliente" class="text-sm font-medium text-texto">E-mail</label>
                                <input type="email" id="email_cliente" name="email_cliente" x-model="form.email_cliente" autocomplete="email" maxlength="190"
                                       aria-required="true" @if ($errors->has('email_cliente')) aria-invalid="true" aria-describedby="erro-email" @endif
                                       class="campo mt-1.5">
                                @error('email_cliente')
                                    <p id="erro-email" class="mt-1.5 text-xs font-medium text-red-700" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex items-start gap-3 border-t border-areia pt-5">
                            <input type="checkbox" id="consentimento" name="consentimento" value="1" x-model="consentimento"
                                   class="mt-0.5 h-4 w-4 rounded border-areia accent-terracota">
                            <label for="consentimento" class="text-sm text-texto/75">
                                Autorizo o contato pelo WhatsApp e e-mail informados, exclusivamente para tratar desta solicitação de agendamento.
                            </label>
                        </div>
                    </div>

                    <div class="mt-6 flex items-center justify-between gap-4">
                        <button type="button" @click="irParaStep(3)" class="link-arrow text-terracota-escuro">
                            <x-ui.icon name="arrow-right" class="h-4 w-4 rotate-180" />
                            Voltar
                        </button>
                        <button type="button" @click="irParaConfirmacao" :disabled="!podeAvancarParaConfirmacao()"
                                class="btn-verde disabled:cursor-not-allowed disabled:opacity-40">
                            Revisar solicitação
                            <x-ui.icon name="arrow-right" class="h-4 w-4" />
                        </button>
                    </div>
                </div>

                {{-- PASSO 5 — Revisão e envio --}}
                <div x-show="step === 5" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                    <h2 class="sr-only">Revisão e envio</h2>
                    <dl class="grid grid-cols-1 gap-x-6 gap-y-5 rounded-[1.75rem] border border-areia bg-white p-6 shadow-[0_24px_50px_-40px_rgb(43_36_32/0.6)] sm:grid-cols-2 sm:p-8">
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-semibold tracking-wide text-texto/45 uppercase">Tratamento</dt>
                            <dd class="mt-1 font-serif text-lg text-verde" x-text="tratamentoSelecionado ? tratamentoSelecionado.nome : ''"></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold tracking-wide text-texto/45 uppercase">Data</dt>
                            <dd class="mt-1 font-serif text-lg text-verde" x-text="dataSelecionadaFormatada"></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold tracking-wide text-texto/45 uppercase">Horário</dt>
                            <dd class="mt-1 font-serif text-lg text-verde" x-text="horaSelecionada"></dd>
                        </div>
                        <div class="sm:col-span-2 border-t border-areia pt-5">
                            <dt class="text-xs font-semibold tracking-wide text-texto/45 uppercase">Nome</dt>
                            <dd class="mt-1 text-base text-texto" x-text="form.nome_cliente"></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold tracking-wide text-texto/45 uppercase">WhatsApp</dt>
                            <dd class="mt-1 text-base text-texto" x-text="form.telefone_cliente"></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold tracking-wide text-texto/45 uppercase">E-mail</dt>
                            <dd class="mt-1 text-base break-all text-texto" x-text="form.email_cliente"></dd>
                        </div>
                    </dl>

                    <div class="mt-5 flex items-start gap-3 rounded-2xl bg-areia/50 p-5 text-sm leading-relaxed text-texto/70">
                        <x-ui.icon name="clock" class="mt-0.5 h-5 w-5 shrink-0 text-terracota-escuro" />
                        <p>
                            Esta é uma <strong class="font-semibold text-texto">solicitação de agendamento</strong>: o horário fica reservado
                            e a confirmação é feita pelo consultório. Se for preciso ajustar algo, o contato será feito pelo WhatsApp ou e-mail informados.
                        </p>
                    </div>

                    <div class="mt-6 flex items-center justify-between gap-4">
                        <button type="button" @click="irParaStep(4)" class="link-arrow text-terracota-escuro">
                            <x-ui.icon name="arrow-right" class="h-4 w-4 rotate-180" />
                            Editar
                        </button>
                        <button type="submit" :disabled="enviando" class="btn-primario disabled:cursor-not-allowed disabled:opacity-60">
                            <span x-show="!enviando">Enviar solicitação</span>
                            <span x-show="enviando" x-cloak>Enviando…</span>
                        </button>
                    </div>
                </div>
            </form>

            <p class="mt-14 text-center text-sm text-texto/60">
                Prefere falar diretamente?
                <a href="{{ $clinica['whatsapp_url'] }}" target="_blank" rel="noopener" class="link-arrow inline-flex text-terracota-escuro">
                    Fale pelo WhatsApp
                    <x-ui.icon name="whatsapp" class="h-4 w-4" />
                </a>
            </p>
        </div>
    </section>
</x-layout>
