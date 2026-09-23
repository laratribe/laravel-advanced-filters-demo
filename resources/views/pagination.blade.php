{{--
    A framework-free paginator.

    Laravel's bundled views assume Tailwind or Bootstrap; this demo loads neither, so the
    default view's inline SVG chevrons render unstyled at full size. This one uses plain
    markup and the .pg-* classes in the layout.
--}}
@if ($paginator->hasPages())
    <nav class="pg" role="navigation" aria-label="Pagination">
        {{-- Previous --}}
        @if ($paginator->onFirstPage())
            <span class="pg__link pg__link--disabled" aria-disabled="true">‹ Prev</span>
        @else
            <a class="pg__link" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹ Prev</a>
        @endif

        {{-- Numbers --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pg__gap" aria-hidden="true">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pg__link pg__link--current" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="pg__link" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next --}}
        @if ($paginator->hasMorePages())
            <a class="pg__link" href="{{ $paginator->nextPageUrl() }}" rel="next">Next ›</a>
        @else
            <span class="pg__link pg__link--disabled" aria-disabled="true">Next ›</span>
        @endif
    </nav>
@endif
