@php
    $meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    $diasSemana = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
    $data = $appointment->data;
    $dataFormatada = "{$diasSemana[$data->dayOfWeek]}, {$data->day} de {$meses[$data->month - 1]} de {$data->year}";
@endphp

<x-layout title="Solicitação enviada — Dra. Gabriela Ferri Paes">
    <section class="relative overflow-hidden px-5 pt-12 pb-24 lg:px-8">
        <div class="textura pointer-events-none absolute inset-0 opacity-50"></div>
        <x-ui.ramo tom="verde" :folhas="11" class="pointer-events-none absolute -bottom-10 -left-14 hidden h-80 w-auto -rotate-[20deg] opacity-90 md:block" />

        <div class="relative mx-auto max-w-xl text-center">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-terracota text-white shadow-[0_14px_30px_-14px_rgb(143_67_35/0.8)]">
                <x-ui.icon name="check" class="h-8 w-8" />
            </span>
            <h1 class="mt-6 font-serif text-4xl font-medium text-verde">Solicitação enviada!</h1>
            <p class="mt-3 text-base text-texto/70">
                Obrigada, {{ \Illuminate\Support\Str::of($appointment->nome_cliente)->explode(' ')->first() }}. Seu horário está reservado e
                aguardando confirmação do consultório.
            </p>

            <dl class="mt-10 space-y-4 rounded-[1.75rem] border border-areia bg-white p-6 text-left sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <dt class="text-sm text-texto/55">Tratamento</dt>
                    <dd class="text-right font-serif text-base text-verde">{{ $appointment->tratamento->nome }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 border-t border-areia pt-4">
                    <dt class="text-sm text-texto/55">Data</dt>
                    <dd class="text-right font-serif text-base text-verde">{{ $dataFormatada }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 border-t border-areia pt-4">
                    <dt class="text-sm text-texto/55">Horário</dt>
                    <dd class="font-serif text-base text-verde">{{ substr($appointment->hora_inicio, 0, 5) }}</dd>
                </div>
                <div class="flex items-start justify-between gap-4 border-t border-areia pt-4">
                    <dt class="text-sm text-texto/55">Status</dt>
                    <dd><x-admin.status-badge :status="$appointment->status" /></dd>
                </div>
            </dl>

            <div class="mt-6 rounded-2xl bg-areia/50 p-5 text-left text-sm leading-relaxed text-texto/70">
                <p class="font-semibold text-texto">Endereço do consultório</p>
                <p class="mt-1">
                    {{ $clinica['endereco']['logradouro'] }} – {{ $clinica['endereco']['bairro'] }}, {{ $clinica['endereco']['cidade'] }} · CEP {{ $clinica['endereco']['cep'] }}
                </p>
            </div>

            <div class="mt-10 flex flex-col justify-center gap-3 sm:flex-row">
                <a href="{{ $clinica['whatsapp_url'] }}" target="_blank" rel="noopener" class="btn-verde">
                    <x-ui.icon name="whatsapp" class="h-4 w-4" />
                    Fale pelo WhatsApp
                </a>
                <a href="{{ route('home') }}" class="btn-contorno">Voltar ao site</a>
            </div>
        </div>
    </section>
</x-layout>
