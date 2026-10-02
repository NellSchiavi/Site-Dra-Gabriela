@php
    $conflito = session('conflito_pendente');
    $tipoBloqueio = old('tipo', 'fechado_total');
@endphp

<x-admin-layout title="Disponibilidade">
    <h1 class="font-serif text-3xl text-verde">Disponibilidade</h1>
    <p class="mt-1 max-w-2xl text-sm text-texto/60">
        Configure os horários de atendimento e bloqueie dias ou horários específicos. Tudo aqui reflete
        imediatamente no agendamento do site.
    </p>

    @if (session('status'))
        <div class="mt-5 rounded-xl border border-verde/25 bg-verde/10 px-4 py-3 text-sm text-verde" role="status">{{ session('status') }}</div>
    @endif

    @if ($errors->any() && ! $conflito)
        <div class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            <ul class="list-disc space-y-1 pl-4">
                @foreach ($errors->all() as $mensagemErro)
                    <li>{{ $mensagemErro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-5">
        {{-- 1. HORÁRIOS SEMANAIS --}}
        <section class="rounded-2xl border border-areia bg-white p-5 sm:p-6 xl:col-span-3">
            <h2 class="font-serif text-xl text-verde">Horários da semana</h2>
            <p class="mt-1 text-sm text-texto/60">Horário de atendimento de cada dia. Desmarque para fechar o dia.</p>

            <div class="mt-5 space-y-2.5">
                @foreach ($dias as $diaInfo)
                    @php($emConflito = $conflito && $conflito['tipo'] === 'regra' && $conflito['dia_da_semana'] === $diaInfo['dia'])
                    <form method="POST" action="{{ route('admin.disponibilidade.horarios.atualizar', $diaInfo['dia']) }}"
                          class="rounded-xl border p-3.5 {{ $diaInfo['ativo'] ? 'border-areia' : 'border-dashed border-areia bg-creme/60' }}">
                        @csrf
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
                            <span class="w-20 shrink-0 font-semibold text-texto">{{ $diaInfo['nome'] }}</span>

                            <label class="flex items-center gap-2 text-sm text-texto/70">
                                <input type="hidden" name="ativo" value="0">
                                <input type="checkbox" name="ativo" value="1" @checked($emConflito ? old('ativo') : $diaInfo['ativo']) class="h-4 w-4 rounded border-areia accent-verde">
                                Atende
                            </label>

                            <label class="flex items-center gap-2 text-sm text-texto/70">
                                das
                                <input type="time" name="hora_inicio" value="{{ $emConflito ? old('hora_inicio') : $diaInfo['hora_inicio'] }}"
                                       class="rounded-lg border border-areia bg-creme px-2 py-1.5 text-sm text-texto focus:border-terracota focus:ring-1 focus:ring-terracota focus:outline-none">
                            </label>
                            <label class="flex items-center gap-2 text-sm text-texto/70">
                                às
                                <input type="time" name="hora_fim" value="{{ $emConflito ? old('hora_fim') : $diaInfo['hora_fim'] }}"
                                       class="rounded-lg border border-areia bg-creme px-2 py-1.5 text-sm text-texto focus:border-terracota focus:ring-1 focus:ring-terracota focus:outline-none">
                            </label>

                            <button type="submit" class="ml-auto rounded-full bg-verde px-4 py-1.5 text-sm font-semibold text-white transition-colors hover:bg-verde-escuro">
                                Salvar
                            </button>
                        </div>

                        @if ($emConflito)
                            <x-admin.disponibilidade.painel-conflito :afetadas="$conflito['afetadas']" />
                        @endif
                    </form>
                @endforeach
            </div>

            <p class="mt-4 text-xs text-texto/50">
                Horários provisórios da demonstração — o site público mostra apenas "segunda a sábado, somente com hora marcada".
            </p>
        </section>

        <div class="space-y-6 xl:col-span-2">
            {{-- 2. DURAÇÃO --}}
            <section class="rounded-2xl border border-areia bg-white p-5 sm:p-6">
                <h2 class="font-serif text-xl text-verde">Duração da consulta</h2>
                <p class="mt-1 text-sm text-texto/60">Define o intervalo entre os horários oferecidos no site.</p>
                <form method="POST" action="{{ route('admin.disponibilidade.duracao.atualizar') }}" class="mt-4 flex items-center gap-3">
                    @csrf
                    <label for="duracao" class="sr-only">Duração da consulta</label>
                    <select id="duracao" name="duracao_consulta_min" class="campo !w-auto">
                        @foreach ([30, 45, 60, 90, 120] as $minutos)
                            <option value="{{ $minutos }}" @selected($duracaoConsulta === $minutos)>{{ $minutos }} minutos</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-full bg-verde px-4 py-2 text-sm font-semibold text-white hover:bg-verde-escuro">Salvar</button>
                </form>
            </section>

            {{-- 3. BLOQUEIOS --}}
            <section class="rounded-2xl border border-areia bg-white p-5 sm:p-6" x-data="{ tipo: @js($tipoBloqueio) }">
                <h2 class="font-serif text-xl text-verde">Bloquear agenda</h2>
                <p class="mt-1 text-sm text-texto/60">Feche um dia inteiro ou apenas uma faixa de horário.</p>

                <form id="form-bloqueio" method="POST" action="{{ route('admin.disponibilidade.bloqueios.salvar') }}" class="mt-4 space-y-3">
                    @csrf
                    <div class="flex rounded-full border border-areia bg-creme p-1 text-sm">
                        <label class="flex-1 cursor-pointer rounded-full px-3 py-1.5 text-center font-semibold transition-colors" :class="tipo === 'fechado_total' ? 'bg-verde text-white' : 'text-texto/60'">
                            <input type="radio" name="tipo" value="fechado_total" x-model="tipo" class="sr-only"> Dia inteiro
                        </label>
                        <label class="flex-1 cursor-pointer rounded-full px-3 py-1.5 text-center font-semibold transition-colors" :class="tipo === 'fechado_parcial' ? 'bg-verde text-white' : 'text-texto/60'">
                            <input type="radio" name="tipo" value="fechado_parcial" x-model="tipo" class="sr-only"> Faixa de horário
                        </label>
                    </div>

                    <div>
                        <label for="bloqueio_data" class="text-xs font-medium text-texto/60">Data</label>
                        <input type="date" id="bloqueio_data" name="data" value="{{ old('data') }}" min="{{ now()->toDateString() }}" class="campo mt-1">
                    </div>

                    <div class="grid grid-cols-2 gap-3" x-show="tipo === 'fechado_parcial'" x-cloak>
                        <div>
                            <label for="bloqueio_inicio" class="text-xs font-medium text-texto/60">Das</label>
                            <input type="time" id="bloqueio_inicio" name="hora_inicio" value="{{ old('hora_inicio') }}" class="campo mt-1" :disabled="tipo !== 'fechado_parcial'">
                        </div>
                        <div>
                            <label for="bloqueio_fim" class="text-xs font-medium text-texto/60">Até</label>
                            <input type="time" id="bloqueio_fim" name="hora_fim" value="{{ old('hora_fim') }}" class="campo mt-1" :disabled="tipo !== 'fechado_parcial'">
                        </div>
                    </div>

                    <div>
                        <label for="bloqueio_motivo" class="text-xs font-medium text-texto/60">Motivo (opcional, uso interno)</label>
                        <input type="text" id="bloqueio_motivo" name="motivo" maxlength="255" value="{{ old('motivo') }}" class="campo mt-1">
                    </div>

                    <button type="submit" class="btn-primario w-full !py-2.5">Bloquear</button>

                    @if ($conflito && $conflito['tipo'] === 'bloqueio')
                        <x-admin.disponibilidade.painel-conflito :afetadas="$conflito['afetadas']" />
                    @endif
                </form>

                <h3 class="mt-7 text-xs font-semibold tracking-wide text-texto/50 uppercase">Bloqueios ativos</h3>
                @if ($bloqueios->isEmpty())
                    <p class="mt-3 text-sm text-texto/50">Nenhum bloqueio cadastrado.</p>
                @else
                    <ul class="mt-2 divide-y divide-areia">
                        @foreach ($bloqueios as $bloqueio)
                            <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                                <div class="text-sm text-texto">
                                    <span class="font-semibold">{{ $bloqueio->data->format('d/m/Y') }}</span>
                                    —
                                    @if ($bloqueio->tipo === \App\Models\AvailabilityException::TIPO_FECHADO_TOTAL)
                                        dia inteiro
                                    @else
                                        {{ substr($bloqueio->hora_inicio, 0, 5) }} às {{ substr($bloqueio->hora_fim, 0, 5) }}
                                    @endif
                                    @if ($bloqueio->motivo)
                                        <span class="text-texto/55">({{ $bloqueio->motivo }})</span>
                                    @endif
                                </div>
                                <form method="POST" action="{{ route('admin.disponibilidade.bloqueios.remover', $bloqueio) }}" x-data="{ confirmando: false }">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" x-show="!confirmando" @click="confirmando = true" class="text-sm font-medium text-red-700 underline underline-offset-2">Remover</button>
                                    <span x-show="confirmando" x-cloak class="inline-flex items-center gap-2 text-sm">
                                        Remover?
                                        <button type="submit" class="font-semibold text-red-700 underline">Sim</button>
                                        <button type="button" @click="confirmando = false" class="text-texto/60 underline">Não</button>
                                    </span>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    </div>
</x-admin-layout>
