@props(['folhas' => 11, 'tom' => 'verde', 'contorno' => false])
@php
    /*
     * Ramo botânico desenhado em código (sem imagem externa): um caule em
     * curva de Bézier com folhas alternadas que acompanham a tangente e
     * diminuem em direção à ponta. "tom" troca só a cor; "contorno" desenha
     * as folhas em traço fino (versão mais delicada, para fundos escuros).
     */
    $cores = [
        'verde' => ['#1e3a2c', '#5d7a5a'],
        'folha' => ['#5d7a5a', '#86a07f'],
        'terracota' => ['#8f4323', '#b4582f'],
        'creme' => ['#f7f1e8', '#e8dcc9'],
    ];
    [$corA, $corB] = $cores[$tom] ?? $cores['verde'];

    // Caule: P0 (base) -> P2 (ponta), controle P1.
    $p0 = [70, 410]; $p1 = [10, 220]; $p2 = [120, 12];
    $ponto = fn ($t) => [
        (1 - $t) ** 2 * $p0[0] + 2 * (1 - $t) * $t * $p1[0] + $t ** 2 * $p2[0],
        (1 - $t) ** 2 * $p0[1] + 2 * (1 - $t) * $t * $p1[1] + $t ** 2 * $p2[1],
    ];
    $tangente = fn ($t) => rad2deg(atan2(
        2 * (1 - $t) * ($p1[1] - $p0[1]) + 2 * $t * ($p2[1] - $p1[1]),
        2 * (1 - $t) * ($p1[0] - $p0[0]) + 2 * $t * ($p2[0] - $p1[0]),
    ));

    $listaFolhas = [];
    for ($i = 0; $i < $folhas; $i++) {
        $t = 0.1 + 0.86 * ($i / max(1, $folhas - 1));
        [$x, $y] = $ponto($t);
        $lado = $i % 2 === 0 ? 1 : -1;
        $listaFolhas[] = [
            'x' => round($x, 1),
            'y' => round($y, 1),
            'angulo' => round($tangente($t) + 52 * $lado, 1),
            'escala' => round(1.35 - 0.6 * $t, 2),
            'cor' => $i % 3 === 0 ? $corB : $corA,
        ];
    }
    [$px, $py] = $ponto(1);
@endphp
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 220 420" aria-hidden="true" focusable="false" {{ $attributes }}>
    <path d="M{{ $p0[0] }} {{ $p0[1] }} Q{{ $p1[0] }} {{ $p1[1] }} {{ $p2[0] }} {{ $p2[1] }}"
          fill="none" stroke="{{ $corA }}" stroke-width="2.2" stroke-linecap="round" />
    @foreach ($listaFolhas as $folha)
        <g transform="translate({{ $folha['x'] }} {{ $folha['y'] }}) rotate({{ $folha['angulo'] }}) scale({{ $folha['escala'] }})">
            @if ($contorno)
                <path d="M0 0C9-10 30-11 44 0 30 11 9 10 0 0Z" fill="none" stroke="{{ $folha['cor'] }}" stroke-width="1.4" />
            @else
                <path d="M0 0C9-10 30-11 44 0 30 11 9 10 0 0Z" fill="{{ $folha['cor'] }}" />
            @endif
            <path d="M3 0H38" stroke="{{ $contorno ? $folha['cor'] : '#f7f1e8' }}" stroke-opacity="{{ $contorno ? '1' : '.35' }}" stroke-width="1" stroke-linecap="round" />
        </g>
    @endforeach
    <g transform="translate({{ round($px, 1) }} {{ round($py, 1) }}) rotate(-70) scale(.7)">
        <path d="M0 0C9-10 30-11 44 0 30 11 9 10 0 0Z" fill="{{ $contorno ? 'none' : $corB }}" stroke="{{ $contorno ? $corB : 'none' }}" stroke-width="1.4" />
    </g>
</svg>
