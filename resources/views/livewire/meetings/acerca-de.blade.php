<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <a href="{{ route('reuniones') }}" wire:navigate class="text-xs font-medium underline" style="color:var(--kairo-text-dim)">&larr; Volver a reuniones</a>
            <h1 class="text-xl font-bold mt-1" style="color:var(--kairo-text)">Acerca de KairoMeet</h1>
        </div>
    </div>
    <div class="kairo-section kairo-sec-respuesta">
        <div class="kairo-content prose-meet">{!! $this->contenidoHtml !!}</div>
    </div>
</div>

<style>
    .prose-meet h1 { font-size:1.4rem;font-weight:800;color:var(--kairo-text);margin-bottom:.6rem; }
    .prose-meet h2 { font-size:1.05rem;font-weight:700;color:var(--kairo-blue-light);margin-top:1.4rem;margin-bottom:.55rem;border-bottom:1px solid var(--kairo-border);padding-bottom:.3rem; }
    .prose-meet p { margin-bottom:.8rem;line-height:1.6; }
    .prose-meet ol,.prose-meet ul { padding-left:1.4rem;margin-bottom:.85rem; }
    .prose-meet ol { list-style:decimal; }.prose-meet ul { list-style:disc; }
    .prose-meet li { margin-bottom:.35rem; }.prose-meet strong { color:var(--kairo-text); }
</style>
