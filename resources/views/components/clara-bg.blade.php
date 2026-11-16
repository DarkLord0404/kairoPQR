{{-- Fondo decorativo: cuadrícula tenue + líneas tipo circuito con pulsos de luz --}}
<div class="c-bg" aria-hidden="true">
    <svg viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice">
        @php
            $lineas = [
                ['M-20 180 H320 L380 120 H760 L820 60 H1460', 'a', ''],
                ['M1460 300 H1180 L1120 360 H880 L840 320 H600', 'b', 'd2'],
                ['M-20 640 H220 L300 560 H520 L580 620 H900 L960 560 H1460', 'a', 'd3'],
                ['M1100 -20 V140 L1160 200 V420 L1220 480 V920', 'a', 'd2'],
                ['M260 920 V760 L320 700 H640 L700 760 H1460', 'b', ''],
                ['M-20 420 C240 380 420 470 640 420 S1040 360 1460 430', 'a', 'd3'],
            ];
        @endphp
        <g class="drift">
            @foreach ($lineas as [$d, $tono, $retraso])
                <path class="ln ln-{{ $tono }}" d="{{ $d }}"/>
                <path class="ln ln-{{ $tono }} pulse {{ $retraso }}" d="{{ $d }}" style="stroke-width:1.8"/>
            @endforeach
            @foreach ([[380,120],[820,60],[1120,360],[580,620],[1160,200],[320,700],[960,560]] as [$x, $y])
                <circle cx="{{ $x }}" cy="{{ $y }}" r="3" fill="rgb(31 42 68 / .14)"/>
            @endforeach
        </g>
    </svg>
</div>
