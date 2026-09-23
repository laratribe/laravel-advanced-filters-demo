{{--
    Shows the actual source behind a demo page.

    Files are read from disk at render time rather than pasted in, so what you see here
    is literally what is running — it cannot drift out of date.

    @param array $files  list of ['path' => relative path, 'note' => optional caption]
    @param string $intro
    @param string $title  summary text; the default only makes sense on a page whose own
                          source is in $files, so the overview page passes its own
    @param bool $open     render expanded instead of collapsed
--}}
@php
    $blocks = collect($files)->map(function ($f) {
        $full = base_path($f['path']);

        return [
            'path' => $f['path'],
            'note' => $f['note'] ?? null,
            'lang' => str_ends_with($f['path'], '.blade.php') ? 'xml' : 'php',
            'code' => is_file($full) ? rtrim(file_get_contents($full)) : "// not found: {$f['path']}",
        ];
    });
@endphp

<details class="code-panel" @if ($open ?? false) open @endif>
    <summary>
        <span class="code-panel__chev" aria-hidden="true">▸</span>
        {{ $title ?? 'The code behind this page' }}
        <span class="code-panel__count">{{ $blocks->count() }} {{ Str::plural('file', $blocks->count()) }}</span>
    </summary>

    <div class="code-panel__body">
        @isset($intro)
            <p class="code-panel__intro">{!! $intro !!}</p>
        @endisset

        @foreach ($blocks as $b)
            <figure class="code-block">
                <figcaption>
                    <code>{{ $b['path'] }}</code>
                    @if ($b['note'])<span>{{ $b['note'] }}</span>@endif
                </figcaption>
                <pre><code class="language-{{ $b['lang'] }}">{{ $b['code'] }}</code></pre>
            </figure>
        @endforeach
    </div>
</details>
