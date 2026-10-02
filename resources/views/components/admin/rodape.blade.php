@props(['escuro' => false])
{{-- Rodapé institucional das telas administrativas (mesmo copyright do site público). --}}
<footer {{ $attributes->merge(['class' => 'flex flex-col gap-2 text-xs sm:flex-row sm:items-center sm:justify-between '.($escuro ? 'text-creme/50' : 'text-texto/45')]) }}>
    <p>© {{ now()->year }} NS | Nelson Schiavi Soluções Digitais. Todos os direitos reservados.</p>
    @if ($clinica['demo'])
        <p class="inline-flex items-center gap-1.5">
            <span class="h-1.5 w-1.5 rounded-full {{ $escuro ? 'bg-[#e8a07c]/70' : 'bg-terracota/60' }}"></span>
            Ambiente de demonstração · Área administrativa
        </p>
    @endif
</footer>
