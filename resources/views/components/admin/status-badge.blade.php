@props(['status'])
@php
    $estilos = [
        'pending' => 'border-terracota/30 bg-terracota/10 text-terracota-escuro',
        'confirmed' => 'border-verde/25 bg-verde/10 text-verde',
        'cancelled' => 'border-red-200 bg-red-50 text-red-700',
        'completed' => 'border-texto/15 bg-texto/5 text-texto',
    ];
@endphp
<span
    {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold ' . ($estilos[$status] ?? 'border-areia bg-areia/30 text-texto')]) }}
>
    {{ \App\Models\Appointment::STATUS_LABELS[$status] ?? $status }}
</span>
