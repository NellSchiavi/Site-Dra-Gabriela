@props(['sobreEscuro' => false, 'alt' => ''])
{{--
    Logo oficial da Dra. Gabriela Ferri Paes — arquivo fornecido pela cliente
    (referencias/referencia/logo.png), apenas recortado e com o fundo creme
    convertido em transparência para a web (public/imagens/logo-gabriela.*).
    "class" define a altura (ex.: h-12); a largura acompanha a proporção.
    Em fundos escuros, fica sobre uma pílula creme, como no arquivo original.
--}}
@php
    $imagem = '<picture class="contents"><source srcset="'.asset('imagens/logo-gabriela.webp').'" type="image/webp">'
        .'<img src="'.asset('imagens/logo-gabriela.png').'" width="195" height="320" alt="'.e($alt).'"'
        .($alt === '' ? ' aria-hidden="true"' : '')
        .' class="'.e(trim('block w-auto '.$attributes->get('class', 'h-12'))).'"></picture>';
@endphp
@if ($sobreEscuro)
    <span class="inline-flex shrink-0 rounded-full bg-creme px-[0.4em] py-[0.35em] text-[0.9rem] shadow-sm">{!! $imagem !!}</span>
@else
    {!! $imagem !!}
@endif
