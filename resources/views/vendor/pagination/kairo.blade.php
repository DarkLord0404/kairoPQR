@if ($paginator->hasPages())
    <nav class="flex items-center justify-between flex-wrap gap-2" style="color: var(--kairo-text-dim)">
        <div class="text-xs">
            Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}
        </div>

        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="kairo-btn-copy text-xs" style="opacity:.4; cursor:default;">&laquo;</span>
            @else
                <button type="button" wire:click="previousPage" wire:loading.attr="disabled" class="kairo-btn-copy text-xs">&laquo;</button>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="text-xs px-2" style="color: var(--kairo-text-dim)">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="kairo-btn-copy text-xs" style="background: rgba(59,130,246,.35); color:#93c5fd;">{{ $page }}</span>
                        @else
                            <button type="button" wire:click="gotoPage({{ $page }})" wire:loading.attr="disabled" class="kairo-btn-copy text-xs">{{ $page }}</button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage" wire:loading.attr="disabled" class="kairo-btn-copy text-xs">&raquo;</button>
            @else
                <span class="kairo-btn-copy text-xs" style="opacity:.4; cursor:default;">&raquo;</span>
            @endif
        </div>
    </nav>
@endif
