<div class="max-w-4xl mx-auto">

    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold" style="color: var(--kairo-text)">Acerca del sistema</h1>
    </div>

    <div class="kairo-section kairo-sec-respuesta">
        <div class="kairo-content prose-acta">{!! $this->contenidoHtml !!}</div>
    </div>
</div>

<style>
    .prose-acta h1 { font-size: 1.4rem; font-weight: 800; color: var(--kairo-text); margin-bottom: .5rem; }
    .prose-acta h2 { font-size: 1.1rem; font-weight: 700; color: var(--kairo-blue-light); margin-top: 1.5rem; margin-bottom: .6rem; border-bottom: 1px solid var(--kairo-border); padding-bottom: .3rem; }
    .prose-acta h3 { font-size: .95rem; font-weight: 700; color: var(--kairo-blue-dim); margin-top: 1.1rem; margin-bottom: .4rem; }
    .prose-acta ul, .prose-acta ol { list-style-position: outside; padding-left: 1.4rem; margin-bottom: .85rem; }
    .prose-acta ul { list-style: disc; }
    .prose-acta ol { list-style: decimal; }
    .prose-acta li { margin-bottom: .3rem; }
    .prose-acta table { width: 100%; font-size: .82rem; margin: .85rem 0; }
    .prose-acta th, .prose-acta td { border: 1px solid var(--kairo-border); padding: .4rem .6rem; text-align: left; }
    .prose-acta th { background: rgba(13,20,36,.9); color: var(--kairo-blue-dim); }
    .prose-acta p { margin-bottom: .85rem; line-height: 1.6; }
    .prose-acta strong { color: var(--kairo-text); }
    .prose-acta code { background: rgba(2,4,10,.65); border: 1px solid var(--kairo-border); border-radius: 4px; padding: .1rem .35rem; font-size: .85em; color: #93c5fd; }
    .prose-acta pre { background: rgba(2,4,10,.65); border: 1px solid var(--kairo-border); border-radius: 8px; padding: .85rem; overflow-x: auto; margin-bottom: .85rem; }
    .prose-acta pre code { background: none; border: none; padding: 0; }
    .prose-acta hr { border-color: var(--kairo-border); margin: 1.25rem 0; }
    .prose-acta a { color: var(--kairo-blue-light); }
    .prose-acta blockquote { border-left: 3px solid var(--kairo-blue); padding-left: .85rem; color: var(--kairo-text-dim); margin-bottom: .85rem; }
</style>
