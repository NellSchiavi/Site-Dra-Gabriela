@props(['appointment'])
<button
    type="button"
    @click="abrirDetalhe({{ $appointment->id }})"
    class="flex w-full flex-col items-start gap-2 rounded-lg border border-areia bg-white px-3 py-2.5 text-left text-sm transition-colors hover:border-terracota hover:bg-areia/20 focus-visible:outline-2 focus-visible:outline-terracota"
>
    <span class="w-full min-w-0">
        <span class="block font-semibold text-texto">
            {{ substr($appointment->hora_inicio, 0, 5) }}–{{ substr($appointment->hora_fim, 0, 5) }}
        </span>
        <span class="mt-0.5 block truncate text-texto/80">{{ $appointment->nome_cliente }}</span>
        <span class="block text-xs text-texto/50">{{ $appointment->tratamento->nome ?? '—' }}</span>
    </span>
    <x-admin.status-badge :status="$appointment->status" class="shrink-0 !px-2 !py-0.5 text-[0.65rem]" />
</button>
