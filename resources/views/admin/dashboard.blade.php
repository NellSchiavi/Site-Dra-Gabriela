@php
    $meses = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
    $diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    $cartoes = [
        ['label' => 'Consultas hoje', 'valor' => $consultasHoje, 'icone' => 'calendar', 'destaque' => false],
        ['label' => 'Solicitações pendentes', 'valor' => $pendentesTotal, 'icone' => 'inbox', 'destaque' => $pendentesTotal > 0],
        ['label' => 'Próximos 7 dias', 'valor' => $proximos7Dias, 'icone' => 'clock', 'destaque' => false],
    ];
@endphp

<x-admin-layout title="Dashboard">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-serif text-3xl text-verde">Dashboard</h1>
            <p class="mt-1 text-sm text-texto/60">Resumo da agenda — {{ now()->format('d/m/Y') }}</p>
        </div>
        <a href="{{ route('admin.agenda') }}" class="btn-verde !px-5 !py-2.5">
            <x-ui.icon name="calendar" class="h-4 w-4" />
            Abrir agenda
        </a>
    </div>

    @if (session('status'))
        <div class="mt-6 rounded-xl border border-verde/25 bg-verde/10 px-4 py-3 text-sm text-verde" role="status">{{ session('status') }}</div>
    @endif
    @error('acao')
        <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $message }}</div>
    @enderror

    <div class="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
        @foreach ($cartoes as $cartao)
            <div class="rounded-2xl border p-5 {{ $cartao['destaque'] ? 'border-terracota/30 bg-terracota/5' : 'border-areia bg-white' }}">
                <div class="flex items-center gap-2 {{ $cartao['destaque'] ? 'text-terracota-escuro' : 'text-texto/55' }}">
                    <x-ui.icon :name="$cartao['icone']" class="h-4 w-4" />
                    <span class="text-xs font-semibold tracking-wide uppercase">{{ $cartao['label'] }}</span>
                </div>
                <p class="mt-3 font-serif text-4xl {{ $cartao['valor'] > 0 ? 'text-verde' : 'text-texto/25' }}">{{ $cartao['valor'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 xl:grid-cols-2">
        {{-- Solicitações pendentes --}}
        <section class="rounded-2xl border border-areia bg-white p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-serif text-xl text-verde">Solicitações pendentes</h2>
                <x-admin.status-badge status="pending" />
            </div>

            @if ($pendentes->isEmpty())
                <p class="mt-6 rounded-xl border border-dashed border-areia px-4 py-8 text-center text-sm text-texto/55">Nenhuma solicitação pendente.</p>
            @else
                <ul class="mt-5 divide-y divide-areia">
                    @foreach ($pendentes as $pendente)
                        <li class="flex flex-wrap items-center gap-4 py-4" x-data="{ cancelando: false }">
                            <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl bg-creme text-verde">
                                <span class="text-[0.65rem] font-semibold uppercase">{{ $diasSemana[$pendente->data->dayOfWeek] }}</span>
                                <span class="font-serif text-lg leading-none">{{ $pendente->data->day }}</span>
                                <span class="text-[0.6rem] text-texto/50 uppercase">{{ $meses[$pendente->data->month - 1] }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-texto">{{ $pendente->nome_cliente }}</p>
                                <p class="text-sm text-texto/60">{{ substr($pendente->hora_inicio, 0, 5) }} · {{ $pendente->tratamento->nome ?? '—' }}</p>
                            </div>
                            <div class="flex items-center gap-2" x-show="!cancelando">
                                <form method="POST" action="{{ route('admin.agenda.confirmar', $pendente) }}">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-1.5 rounded-full bg-verde px-4 py-2 text-sm font-semibold text-white hover:bg-verde-escuro">
                                        <x-ui.icon name="check" class="h-4 w-4" /> Confirmar
                                    </button>
                                </form>
                                <button type="button" @click="cancelando = true" class="rounded-full border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Cancelar</button>
                            </div>
                            <form method="POST" action="{{ route('admin.agenda.cancelar', $pendente) }}" x-show="cancelando" x-cloak class="flex w-full items-center justify-end gap-2 text-sm">
                                @csrf
                                <span class="text-texto/70">Cancelar esta solicitação?</span>
                                <button type="submit" class="rounded-full bg-red-600 px-4 py-2 font-semibold text-white hover:bg-red-700">Sim, cancelar</button>
                                <button type="button" @click="cancelando = false" class="rounded-full border border-areia px-4 py-2 font-semibold text-texto">Voltar</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Próximos agendamentos --}}
        <section class="rounded-2xl border border-areia bg-white p-5 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <h2 class="font-serif text-xl text-verde">Próximos agendamentos</h2>
                <x-admin.status-badge status="confirmed" />
            </div>

            @if ($proximos->isEmpty())
                <p class="mt-6 rounded-xl border border-dashed border-areia px-4 py-8 text-center text-sm text-texto/55">Nenhum agendamento confirmado à frente.</p>
            @else
                <ul class="mt-5 divide-y divide-areia">
                    @foreach ($proximos as $proximo)
                        <li class="flex items-center gap-4 py-4">
                            <div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl bg-verde text-creme">
                                <span class="text-[0.65rem] font-semibold uppercase opacity-75">{{ $diasSemana[$proximo->data->dayOfWeek] }}</span>
                                <span class="font-serif text-lg leading-none">{{ $proximo->data->day }}</span>
                                <span class="text-[0.6rem] uppercase opacity-60">{{ $meses[$proximo->data->month - 1] }}</span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-semibold text-texto">{{ $proximo->nome_cliente }}</p>
                                <p class="text-sm text-texto/60">{{ substr($proximo->hora_inicio, 0, 5) }}–{{ substr($proximo->hora_fim, 0, 5) }} · {{ $proximo->tratamento->nome ?? '—' }}</p>
                            </div>
                            <a href="{{ route('admin.agenda', ['visao' => 'dia', 'data' => $proximo->data->toDateString()]) }}" class="link-arrow text-terracota-escuro">
                                Ver <x-ui.icon name="arrow-right" class="h-4 w-4" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-admin-layout>
