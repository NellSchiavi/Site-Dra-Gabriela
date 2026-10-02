@php
    $meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    $diasSemanaAbrev = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

    $formatarData = fn ($data) => "{$data->day} de {$meses[$data->month - 1]} de {$data->year}";

    $paramsBase = [
        'visao' => $visao,
        'status' => $statusFiltro,
        'tratamento_id' => $tratamentoFiltro,
        'busca' => $buscaFiltro,
    ];

    $diaAnterior = route('admin.agenda', array_merge($paramsBase, ['visao' => 'dia', 'data' => $dataSelecionada->subDay()->toDateString()]));
    $diaSeguinte = route('admin.agenda', array_merge($paramsBase, ['visao' => 'dia', 'data' => $dataSelecionada->addDay()->toDateString()]));
    $semanaAnterior = route('admin.agenda', array_merge($paramsBase, ['visao' => 'semana', 'data' => $inicioIntervalo->subWeek()->toDateString()]));
    $semanaSeguinte = route('admin.agenda', array_merge($paramsBase, ['visao' => 'semana', 'data' => $inicioIntervalo->addWeek()->toDateString()]));
    $hoje = route('admin.agenda', array_merge($paramsBase, ['visao' => $visao, 'data' => now()->toDateString()]));
@endphp

<x-admin-layout title="Agenda">
    <div x-data="agendaAdministrativa()">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="font-serif text-2xl font-semibold text-texto">Agenda</h1>
                <p class="mt-1 text-sm text-texto/60">
                    {{ $visao === 'semana' ? $formatarData($inicioIntervalo) . ' – ' . $formatarData($fimIntervalo) : $formatarData($dataSelecionada) }}
                </p>
            </div>

            {{-- Alternância dia/semana --}}
            <div class="flex rounded-full border border-areia bg-white p-1 text-sm">
                <a
                    href="{{ route('admin.agenda', array_merge($paramsBase, ['visao' => 'dia', 'data' => $dataSelecionada->toDateString()])) }}"
                    class="rounded-full px-4 py-1.5 font-semibold transition-colors {{ $visao === 'dia' ? 'bg-verde text-white' : 'text-texto/60 hover:text-texto' }}"
                >
                    Dia
                </a>
                <a
                    href="{{ route('admin.agenda', array_merge($paramsBase, ['visao' => 'semana', 'data' => $dataSelecionada->toDateString()])) }}"
                    class="rounded-full px-4 py-1.5 font-semibold transition-colors {{ $visao === 'semana' ? 'bg-verde text-white' : 'text-texto/60 hover:text-texto' }}"
                >
                    Semana
                </a>
            </div>
        </div>

        {{-- Feedback de ações --}}
        @if (session('status'))
            <div class="mt-4 rounded-lg border border-verde/25 bg-verde/10 px-4 py-3 text-sm text-verde" role="status">
                {{ session('status') }}
            </div>
        @endif
        @error('acao')
            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
                {{ $message }}
            </div>
        @enderror

        {{-- Navegação por data --}}
        <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <a href="{{ $visao === 'semana' ? $semanaAnterior : $diaAnterior }}" aria-label="{{ $visao === 'semana' ? 'Semana anterior' : 'Dia anterior' }}"
                   class="flex h-9 w-9 items-center justify-center rounded-full border border-areia text-texto transition-colors hover:border-terracota">
                    <x-ui.icon name="arrow-right" class="h-4 w-4 rotate-180" />
                </a>
                <a href="{{ $hoje }}" class="rounded-full border border-areia px-4 py-1.5 text-sm font-semibold text-texto transition-colors hover:border-terracota">
                    Hoje
                </a>
                <a href="{{ $visao === 'semana' ? $semanaSeguinte : $diaSeguinte }}" aria-label="{{ $visao === 'semana' ? 'Próxima semana' : 'Próximo dia' }}"
                   class="flex h-9 w-9 items-center justify-center rounded-full border border-areia text-texto transition-colors hover:border-terracota">
                    <x-ui.icon name="arrow-right" class="h-4 w-4" />
                </a>
            </div>

            {{-- Filtros + busca (condição adicional aos filtros) --}}
            <form method="GET" action="{{ route('admin.agenda') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="visao" value="{{ $visao }}">
                <label for="filtro-busca" class="sr-only">Buscar por nome, e-mail ou telefone</label>
                <input
                    type="text" id="filtro-busca" name="busca" value="{{ $buscaFiltro }}"
                    placeholder="Buscar por nome, e-mail ou telefone"
                    class="w-56 rounded-lg border border-areia bg-white px-3 py-1.5 text-sm text-texto focus:border-terracota focus:ring-1 focus:ring-terracota focus:outline-none"
                >

                <label for="filtro-data" class="sr-only">Data</label>
                <input
                    type="date" id="filtro-data" name="data" value="{{ $dataSelecionada->toDateString() }}"
                    class="rounded-lg border border-areia bg-white px-3 py-1.5 text-sm text-texto focus:border-terracota focus:ring-1 focus:ring-terracota focus:outline-none"
                >

                <label for="filtro-status" class="sr-only">Status</label>
                <select id="filtro-status" name="status" class="rounded-lg border border-areia bg-white px-3 py-1.5 text-sm text-texto focus:border-terracota focus:ring-1 focus:ring-terracota focus:outline-none">
                    <option value="">Todos os status</option>
                    @foreach (\App\Models\Appointment::STATUS_LABELS as $valor => $rotulo)
                        <option value="{{ $valor }}" @selected($statusFiltro === $valor)>{{ $rotulo }}</option>
                    @endforeach
                </select>

                <label for="filtro-tratamento" class="sr-only">Tratamento</label>
                <select id="filtro-tratamento" name="tratamento_id" class="rounded-lg border border-areia bg-white px-3 py-1.5 text-sm text-texto focus:border-terracota focus:ring-1 focus:ring-terracota focus:outline-none">
                    <option value="">Todos os tratamentos</option>
                    @foreach ($tratamentos as $tratamento)
                        <option value="{{ $tratamento->id }}" @selected((string) $tratamentoFiltro === (string) $tratamento->id)>{{ $tratamento->nome }}</option>
                    @endforeach
                </select>

                <button type="submit" class="rounded-full bg-verde px-4 py-1.5 text-sm font-semibold text-white transition-colors hover:bg-verde-escuro">
                    Filtrar
                </button>
                @if ($statusFiltro || $tratamentoFiltro || $buscaFiltro)
                    <a href="{{ route('admin.agenda', ['visao' => $visao, 'data' => $dataSelecionada->toDateString()]) }}" class="text-sm font-medium text-terracota-escuro underline underline-offset-2">
                        Limpar filtros
                    </a>
                @endif
            </form>
        </div>

        {{-- VISÃO DIÁRIA --}}
        @if ($visao === 'dia')
            @php($consultasDoDia = $consultasPorDia->get($dataSelecionada->toDateString(), collect()))
            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <h2 class="text-xs font-semibold tracking-wide text-texto/50 uppercase">
                        Agendamentos ({{ $consultasDoDia->count() }})
                    </h2>

                    @if ($consultasDoDia->isEmpty())
                        <div class="mt-3 rounded-2xl border border-dashed border-areia bg-white/60 px-5 py-8 text-center text-sm text-texto/60">
                            @if ($buscaFiltro)
                                Nenhuma consulta encontrada para essa busca.
                            @elseif ($statusFiltro || $tratamentoFiltro)
                                Nenhuma consulta encontrada com esses filtros para este dia.
                            @else
                                Nenhum agendamento para este dia.
                            @endif
                        </div>
                    @else
                        <div class="mt-3 space-y-2">
                            @foreach ($consultasDoDia as $appointment)
                                <x-admin.agenda.cartao-consulta :appointment="$appointment" />
                            @endforeach
                        </div>
                    @endif
                </div>

                <div>
                    <h2 class="text-xs font-semibold tracking-wide text-texto/50 uppercase">Horários livres</h2>

                    @if (empty($horariosLivres))
                        <div class="mt-3 rounded-2xl border border-dashed border-areia bg-white/60 px-5 py-8 text-center text-sm text-texto/60">
                            Nenhum horário livre neste dia.
                        </div>
                    @else
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($horariosLivres as $slot)
                                <span class="rounded-full border border-terracota/30 bg-terracota/5 px-3 py-1 text-xs font-medium text-terracota-escuro">
                                    {{ $slot['inicio'] }}–{{ $slot['fim'] }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @else
            {{-- VISÃO SEMANAL: 1 coluna no mobile, 2 no tablet, 7 no desktop —
                 nunca comprime a grade desktop numa tela pequena. --}}
            <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-7">
                @for ($i = 0; $i < 7; $i++)
                    @php($diaColuna = $inicioIntervalo->addDays($i))
                    @php($consultasColuna = $consultasPorDia->get($diaColuna->toDateString(), collect()))
                    <div class="rounded-2xl border border-areia bg-white p-3 {{ $diaColuna->isSameDay(now()) ? 'ring-1 ring-terracota' : '' }}">
                        <p class="text-xs font-semibold tracking-wide text-texto/50 uppercase">
                            {{ $diasSemanaAbrev[$diaColuna->dayOfWeek] }} · {{ $diaColuna->day }}
                        </p>

                        @if ($consultasColuna->isEmpty())
                            <p class="mt-3 text-xs text-texto/50">Sem agendamentos.</p>
                        @else
                            <div class="mt-2 space-y-2">
                                @foreach ($consultasColuna as $appointment)
                                    <x-admin.agenda.cartao-consulta :appointment="$appointment" />
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endfor
            </div>
        @endif

        {{-- Modal de detalhe do agendamento. Auditoria de overflow:
             a viewport pode ser mais baixa que o conteúdo (observação longa,
             ações + confirmação abertas ao mesmo tempo) — o card interno
             precisa de altura máxima e rolagem própria, nunca ultrapassando
             a viewport sem forma de alcançar o restante do conteúdo. --}}
        <div
            x-show="modalAberto"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-texto/50 p-4"
            role="dialog"
            aria-modal="true"
            aria-label="Detalhe do agendamento"
            @keydown.escape.window="fecharModal()"
            @keydown.tab="aprisionarFoco($event)"
        >
            <div @click.outside="fecharModal()" class="max-h-[calc(100vh-2rem)] w-full max-w-md overflow-y-auto rounded-2xl bg-white shadow-lg">
                {{-- Cabeçalho fixo durante a rolagem interna — o botão Fechar
                     precisa continuar acessível sem precisar rolar de volta
                     ao topo do card (auditoria de overflow). --}}
                <div class="sticky top-0 z-10 flex items-center justify-between bg-white px-6 pt-6 pb-4">
                    <h2 class="font-serif text-lg font-semibold text-texto">Detalhe do agendamento</h2>
                    <button type="button" x-ref="botaoFechar" @click="fecharModal()" class="rounded-full p-1.5 text-texto/60 hover:bg-areia/40" aria-label="Fechar">
                        <x-ui.icon name="close" class="h-4 w-4" />
                    </button>
                </div>
                <div class="px-6 pb-6">

                {{-- Carregando --}}
                <p x-show="carregando" x-cloak class="mt-6 text-center text-sm text-texto/60">Carregando…</p>

                {{-- Erro técnico — nunca falha silenciosamente (mesmo padrão do fluxo público) --}}
                <div x-show="!carregando && erro" x-cloak class="mt-6 rounded-lg border border-red-200 bg-red-50 px-4 py-4 text-center" role="alert">
                    <p class="text-sm text-red-800" x-text="erro"></p>
                    <button type="button" @click="tentarNovamente()" class="btn-verde mt-3">Tentar novamente</button>
                </div>

                {{-- Detalhe carregado --}}
                <template x-if="!carregando && !erro && detalhe">
                    <div class="mt-5">
                        <dl class="space-y-3 text-sm">
                            <div class="flex items-center justify-between">
                                <dt class="text-texto/50">Status</dt>
                                <dd x-text="detalhe.status_label" class="font-semibold text-texto"></dd>
                            </div>
                            <div><dt class="text-texto/50">Nome</dt><dd x-text="detalhe.nome_cliente" class="font-medium text-texto"></dd></div>
                            <div><dt class="text-texto/50">E-mail</dt><dd x-text="detalhe.email_cliente" class="text-texto"></dd></div>
                            <div>
                                <dt class="text-texto/50">WhatsApp</dt>
                                <dd class="flex flex-wrap items-center gap-2 text-texto">
                                    <span x-text="detalhe.telefone_cliente"></span>
                                    <a x-show="detalhe.whatsapp_url" :href="detalhe.whatsapp_url" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs font-semibold text-terracota-escuro underline underline-offset-2">
                                        Abrir conversa <x-ui.icon name="external" class="h-3.5 w-3.5" />
                                    </a>
                                </dd>
                            </div>
                            <div><dt class="text-texto/50">Tratamento</dt><dd x-text="detalhe.tratamento" class="text-texto"></dd></div>
                            <div>
                                <dt class="text-texto/50">Data e horário</dt>
                                <dd class="text-texto"><span x-text="detalhe.data"></span> · <span x-text="detalhe.hora_inicio"></span>–<span x-text="detalhe.hora_fim"></span></dd>
                            </div>
                            <div x-show="detalhe.cancelled_reason">
                                <dt class="text-texto/50">Motivo do cancelamento</dt>
                                <dd x-text="detalhe.cancelled_reason" class="text-texto"></dd>
                            </div>
                            <div x-show="detalhe.created_at">
                                <dt class="text-texto/50">Solicitado em</dt>
                                <dd x-text="detalhe.created_at" class="text-texto/70"></dd>
                            </div>
                        </dl>

                        {{-- Ações: só as válidas para o status atual --}}
                        <div class="mt-6 border-t border-areia pt-5" x-show="!confirmando">
                            <template x-if="detalhe.status === 'confirmed' || detalhe.status === 'pending'">
                                <div class="flex flex-wrap gap-2">
                                    <template x-if="detalhe.status === 'pending'">
                                        <button type="button" @click="pedirConfirmacao('confirmar')" class="rounded-full bg-verde px-4 py-2 text-sm font-semibold text-white hover:bg-verde-escuro">
                                            Confirmar
                                        </button>
                                    </template>
                                    <template x-if="detalhe.status === 'confirmed'">
                                        <button type="button" @click="pedirConfirmacao('concluir')" class="rounded-full border border-areia px-4 py-2 text-sm font-semibold text-texto hover:bg-areia/30">
                                            Marcar como concluída
                                        </button>
                                    </template>
                                    <button type="button" @click="pedirConfirmacao('cancelar')" class="rounded-full border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">
                                        Cancelar
                                    </button>
                                </div>
                            </template>
                            <template x-if="!(detalhe.status === 'confirmed' || detalhe.status === 'pending')">
                                <p class="text-sm text-texto/50">Nenhuma ação disponível para este status.</p>
                            </template>
                        </div>

                        {{-- Confirmação explícita antes de cada ação --}}
                        <div x-show="confirmando" x-cloak class="mt-6 border-t border-areia pt-5">
                            <p class="text-sm font-medium text-texto" x-text="textoConfirmacao()"></p>

                            <template x-if="confirmando === 'cancelar'">
                                <form :action="`/admin/agenda/${detalhe?.id}/cancelar`" method="POST" @submit="enviando = true" class="mt-3">
                                    @csrf
                                    <label for="motivo-cancelamento" class="text-xs font-medium text-texto/60">Motivo (opcional)</label>
                                    <textarea id="motivo-cancelamento" name="motivo" x-model="motivoCancelamento" rows="2"
                                              class="mt-1 w-full rounded-lg border border-areia bg-creme px-3 py-2 text-sm text-texto focus:border-terracota focus:ring-1 focus:ring-terracota focus:outline-none"></textarea>
                                    <div class="mt-3 flex gap-2">
                                        <button type="submit" :disabled="enviando" class="rounded-full bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60">
                                            <span x-show="!enviando">Sim, cancelar</span>
                                            <span x-show="enviando" x-cloak>Enviando…</span>
                                        </button>
                                        <button type="button" @click="confirmando = null" class="rounded-full border border-areia px-4 py-2 text-sm font-semibold text-texto hover:bg-areia/30">Voltar</button>
                                    </div>
                                </form>
                            </template>

                            <template x-if="confirmando === 'concluir'">
                                <form :action="`/admin/agenda/${detalhe?.id}/concluir`" method="POST" @submit="enviando = true" class="mt-3">
                                    @csrf
                                    <div class="flex gap-2">
                                        <button type="submit" :disabled="enviando" class="btn-verde !px-4 !py-2 disabled:cursor-not-allowed disabled:opacity-60">
                                            <span x-show="!enviando">Sim, marcar como concluída</span>
                                            <span x-show="enviando" x-cloak>Enviando…</span>
                                        </button>
                                        <button type="button" @click="confirmando = null" class="rounded-full border border-areia px-4 py-2 text-sm font-semibold text-texto hover:bg-areia/30">Voltar</button>
                                    </div>
                                </form>
                            </template>

                            <template x-if="confirmando === 'confirmar'">
                                <form :action="`/admin/agenda/${detalhe?.id}/confirmar`" method="POST" @submit="enviando = true" class="mt-3">
                                    @csrf
                                    <div class="flex gap-2">
                                        <button type="submit" :disabled="enviando" class="btn-verde !px-4 !py-2 disabled:cursor-not-allowed disabled:opacity-60">
                                            <span x-show="!enviando">Sim, confirmar</span>
                                            <span x-show="enviando" x-cloak>Enviando…</span>
                                        </button>
                                        <button type="button" @click="confirmando = null" class="rounded-full border border-areia px-4 py-2 text-sm font-semibold text-texto hover:bg-areia/30">Voltar</button>
                                    </div>
                                </form>
                            </template>
                        </div>
                    </div>
                </template>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>
