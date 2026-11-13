{{-- Laporan Inspeksi Auto18: diagram mobil tampak atas + batang skor per kategori. Param: $car --}}
@php
    $categories = config('auto18.inspection_categories');
    $scores = [];
    foreach ($categories as $c) {
        $scores[$c['key']] = (int) ($car->inspection[$c['key']] ?? 0);
    }
    $lowestKey = collect($categories)->sortBy(fn ($c) => $scores[$c['key']])->first()['key'];

    // Nama kelas ditulis utuh agar tidak dibuang oleh purge Tailwind
    $tone = fn (int $s) => $s >= 90
        ? ['fill-white/15 stroke-white/40', 'text-white', 'Sangat baik', 'bg-white']
        : ($s >= 80
            ? ['fill-amber-400/25 stroke-amber-400/60', 'text-amber-400', 'Baik', 'bg-amber-400']
            : ['fill-brand-600/35 stroke-brand-600/70', 'text-brand-400', 'Perlu perhatian', 'bg-brand-600']);

    $body = 'M65 10 C89 10 105 12 108 30 L111 80 L111 200 L109 232 C107 246 91 250 65 250 C39 250 23 246 21 232 L19 200 L19 80 L22 30 C25 12 41 10 65 10 Z';
    $mirrorL = 'M20 98 L12 95 Q9 95 9 98 L9 104 Q9 107 12 107 L20 106 Z';
    $mirrorR = 'M110 98 L118 95 Q121 95 121 98 L121 104 Q121 107 118 107 L110 106 Z';
    $docCard = 'M140 100 Q140 96 144 96 L178 96 L190 108 L190 160 Q190 164 186 164 L144 164 Q140 164 140 160 Z';
    $zones = [
        'kakiKaki' => [['x' => 10, 'y' => 46, 'w' => 14, 'h' => 34, 'rx' => 3.5], ['x' => 106, 'y' => 46, 'w' => 14, 'h' => 34, 'rx' => 3.5], ['x' => 10, 'y' => 180, 'w' => 14, 'h' => 34, 'rx' => 3.5], ['x' => 106, 'y' => 180, 'w' => 14, 'h' => 34, 'rx' => 3.5]],
        'eksterior' => [['d' => $body], ['d' => $mirrorL], ['d' => $mirrorR]],
        'mesin' => [['x' => 28, 'y' => 31, 'w' => 74, 'h' => 58, 'rx' => 8]],
        'kelistrikan' => [['x' => 31, 'y' => 19, 'w' => 18, 'h' => 7, 'rx' => 3.5], ['x' => 81, 'y' => 19, 'w' => 18, 'h' => 7, 'rx' => 3.5], ['x' => 34, 'y' => 112, 'w' => 62, 'h' => 6, 'rx' => 3]],
        'interior' => [['x' => 30, 'y' => 121, 'w' => 70, 'h' => 75, 'rx' => 8]],
        'transmisi' => [['x' => 59, 'y' => 124, 'w' => 12, 'h' => 40, 'rx' => 4]],
        'dokumen' => [['d' => $docCard]],
    ];
    $details = [
        'eksterior' => [['d' => 'M19 158 L30 158', 'line' => true], ['d' => 'M100 158 L111 158', 'line' => true]],
        'interior' => [['x' => 36, 'y' => 128, 'w' => 20, 'h' => 24, 'rx' => 5], ['x' => 74, 'y' => 128, 'w' => 20, 'h' => 24, 'rx' => 5], ['x' => 36, 'y' => 169, 'w' => 58, 'h' => 21, 'rx' => 6]],
        'transmisi' => [['cx' => 65, 'cy' => 136, 'r' => 3.5]],
        'dokumen' => [['d' => 'M178 96 L178 108 L190 108', 'line' => true], ['x' => 147, 'y' => 120, 'w' => 32, 'h' => 2.5, 'rx' => 1.25], ['x' => 147, 'y' => 144, 'w' => 24, 'h' => 2.5, 'rx' => 1.25], ['x' => 147, 'y' => 151, 'w' => 34, 'h' => 2.5, 'rx' => 1.25]],
    ];
    $shape = function (array $s, string $cls = '') {
        if (isset($s['d'])) return '<path d="' . $s['d'] . '" class="' . $cls . '"/>';
        if (isset($s['r'])) return '<circle cx="' . $s['cx'] . '" cy="' . $s['cy'] . '" r="' . $s['r'] . '" class="' . $cls . '"/>';
        return '<rect x="' . $s['x'] . '" y="' . $s['y'] . '" width="' . $s['w'] . '" height="' . $s['h'] . '" rx="' . $s['rx'] . '" class="' . $cls . '"/>';
    };
    $drawOrder = ['kakiKaki', 'eksterior', 'mesin', 'kelistrikan', 'interior', 'transmisi', 'dokumen'];
    $renderZone = function (string $key) use ($zones, $details, $scores, $tone, $shape) {
        $t = $tone($scores[$key]);
        $out = '<g class="cursor-pointer" x-on:mouseenter="sel = \'' . $key . '\'" x-on:click="sel = \'' . $key . '\'">';
        foreach ($zones[$key] as $z) $out .= $shape($z, 'fill-ink-900');
        foreach ($zones[$key] as $z) $out .= $shape($z, $t[0]);
        foreach ($details[$key] ?? [] as $d) $out .= $shape($d, !empty($d['line']) ? 'fill-none ' . explode(' ', $t[0])[1] : $t[0]);
        if ($key === 'dokumen') $out .= '<g class="pointer-events-none select-none fill-white/85 font-display" font-size="9" font-weight="900" letter-spacing="0.6"><text x="147" y="115">BPKB</text><text x="147" y="139">STNK</text></g>';
        return $out . '</g>';
    };
@endphp
<div class="a18-card p-5" x-data="{ sel: '{{ $lowestKey }}', labels: @js(collect($categories)->pluck('label', 'key')), scores: @js($scores) }">
    <div class="flex items-center gap-4">
        <div class="flex h-16 w-16 shrink-0 flex-col items-center justify-center rounded-full border-4 border-brand-600 bg-ink-900">
            <span class="font-display text-xl font-black leading-none text-white">{{ $car->inspection_score }}</span>
            <span class="text-[9px] uppercase text-neutral-500">dari 100</span>
        </div>
        <div>
            <h3 class="flex items-center gap-1.5 font-display text-sm font-black uppercase tracking-wide text-white">
                <x-lucide-badge-check class="h-4 w-4 text-brand-500" aria-hidden="true" /> Laporan Inspeksi Auto18
            </h3>
            <p class="mt-1 text-xs text-neutral-400">Mesin, transmisi, kaki-kaki, kelistrikan, interior, eksterior, dan dokumen diperiksa sebelum dijual.</p>
        </div>
    </div>

    <div class="mt-4 grid gap-5 md:grid-cols-[200px_minmax(0,1fr)] md:items-center">
        {{-- Diagram: penunjuk untuk mouse/sentuh; kontrol yang aksesibel adalah tombol batang skor --}}
        <div class="mx-auto w-full max-w-[200px] md:max-w-none">
            <svg viewBox="0 0 198 260" class="block h-auto w-full" aria-hidden="true" focusable="false">
                {!! $renderZone('kakiKaki') . $renderZone('eksterior') !!}
                <g class="pointer-events-none fill-ink-700 stroke-white/25">
                    <path d="M33 110 L37 95 Q65 90 93 95 L97 110 Q65 107 33 110 Z" />
                    <path d="M33 199 Q65 202 97 199 L93 212 Q65 215 37 212 Z" />
                </g>
                {!! collect(array_slice($drawOrder, 2))->map($renderZone)->implode('') !!}
                <g class="pointer-events-none fill-none stroke-brand-600" stroke-width="2.5" stroke-linejoin="round">
                    @foreach ($zones as $key => $list)
                        <g x-show="sel === '{{ $key }}'" x-cloak>{!! collect($list)->map(fn ($z) => $shape($z))->implode('') !!}</g>
                    @endforeach
                </g>
            </svg>
            <p class="mt-3 min-h-[2.5rem] text-center text-xs leading-snug">
                <span class="block font-bold text-white" x-text="labels[sel]"></span>
                <span class="text-neutral-400"><span class="font-bold text-white" x-text="scores[sel]"></span>/100 &middot; </span>
                <span class="font-bold" x-bind:class="scores[sel] >= 90 ? 'text-white' : (scores[sel] >= 80 ? 'text-amber-400' : 'text-brand-400')" x-text="scores[sel] >= 90 ? 'Sangat baik' : (scores[sel] >= 80 ? 'Baik' : 'Perlu perhatian')"></span>
            </p>
        </div>

        <div role="group" aria-label="Skor per kategori inspeksi" class="-mx-2.5 space-y-0.5">
            @foreach ($categories as $i => $category)
                @php $score = $scores[$category['key']]; @endphp
                <button type="button" x-on:click="sel = '{{ $category['key'] }}'" x-on:focus="sel = '{{ $category['key'] }}'" x-on:mouseenter="sel = '{{ $category['key'] }}'"
                        x-bind:aria-pressed="sel === '{{ $category['key'] }}'"
                        class="relative block w-full rounded-lg border border-transparent px-2.5 py-1.5 text-left transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400"
                        x-bind:class="sel === '{{ $category['key'] }}' ? '!border-brand-600/80 bg-brand-600/10' : ''">
                    <span class="flex items-center justify-between text-xs">
                        <span class="text-neutral-300">{{ $category['label'] }}</span>
                        <span class="font-bold text-white">{{ $score }}</span>
                    </span>
                    <span class="mt-1 block h-1.5 overflow-hidden rounded-full bg-white/10">
                        <span class="a18-bar block h-full rounded-full {{ $tone($score)[3] }}" style="width: {{ $score }}%; animation-delay: {{ 0.2 + $i * 0.04 }}s"></span>
                    </span>
                </button>
            @endforeach
        </div>
    </div>
</div>
