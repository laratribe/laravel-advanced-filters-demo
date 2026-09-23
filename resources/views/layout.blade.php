<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Advanced Filters — Demo')</title>

    {{-- Published by: php artisan vendor:publish --tag=advanced-filters-assets
         Cache-busted on mtime, because browsers cache ES modules hard. --}}
    <link rel="stylesheet" href="{{ asset('vendor/advanced-filters/advanced-filters.css') }}?v={{ filemtime(public_path('vendor/advanced-filters/advanced-filters.css')) }}">

    {{-- The browser bundle lives in @highlightjs/cdn-assets — the highlight.js npm
         package only ships CommonJS modules, so /lib/highlight.min.js is a 404. --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@highlightjs/cdn-assets@11.9.0/styles/atom-one-dark.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@highlightjs/cdn-assets@11.9.0/highlight.min.js" defer></script>
    <script>
        // The deferred bundle is guaranteed to have run by DOMContentLoaded. <details>
        // content is in the DOM even while collapsed, so nothing needs re-highlighting.
        window.addEventListener('DOMContentLoaded', () => window.hljs?.highlightAll())
    </script>

    @stack('head')

    <style>
        :root { --ink:#0f172a; --muted:#64748b; --line:#e2e8f0; --accent:#4f46e5; }
        * { box-sizing: border-box; }
        body { font-family: ui-sans-serif, system-ui, sans-serif; margin:0; background:#f8fafc; color:var(--ink); }
        .wrap { max-width: 1120px; margin: 0 auto; padding: 2.5rem 1.5rem 4rem; }
        h1 { font-size: 1.5rem; letter-spacing:-.02em; margin:0 0 .35rem; }
        .lede { color: var(--muted); font-size:.9375rem; margin:0 0 1.5rem; }
        nav { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1.75rem; }
        nav a { padding:.4rem .85rem; border:1px solid var(--line); border-radius:8px; background:#fff;
                text-decoration:none; color:var(--ink); font-size:.875rem; }
        nav a[aria-current="page"] { background:var(--accent); border-color:var(--accent); color:#fff; font-weight:600; }
        .card { background:#fff; border:1px solid var(--line); border-radius:12px; padding:1rem 1.25rem; margin-bottom:1.25rem; }
        table { width:100%; border-collapse:collapse; background:#fff; border:1px solid var(--line);
                border-radius:12px; overflow:hidden; font-size:.875rem; }
        th,td { text-align:left; padding:.6rem .8rem; border-bottom:1px solid #f1f5f9; }
        th { background:#f8fafc; font-weight:600; font-size:.75rem; text-transform:uppercase;
             letter-spacing:.04em; color:var(--muted); }
        td.num, th.num { text-align:right; font-variant-numeric: tabular-nums; }
        .pill { display:inline-block; padding:.1rem .5rem; border-radius:999px; font-size:.75rem;
                background:#eef2ff; color:#3730a3; }
        .meta { display:flex; justify-content:space-between; align-items:center; gap:1rem;
                flex-wrap:wrap; margin-top:1rem; color:var(--muted); font-size:.8125rem; }
        .meta strong { color:var(--ink); font-weight:600; }
        .pg { display:flex; gap:.25rem; flex-wrap:wrap; align-items:center; }
        .pg__link { display:inline-flex; align-items:center; justify-content:center;
                min-width:2rem; height:2rem; padding:0 .55rem; border:1px solid var(--line);
                border-radius:7px; background:#fff; color:var(--accent); text-decoration:none;
                font-size:.8125rem; font-variant-numeric: tabular-nums; }
        .pg__link:hover { border-color:var(--accent); }
        .pg__link--current { background:var(--accent); border-color:var(--accent);
                color:#fff; font-weight:600; }
        .pg__link--disabled { color:#cbd5e1; background:#f8fafc; }
        .pg__link--disabled:hover { border-color:var(--line); }
        .pg__gap { padding:0 .2rem; color:var(--muted); }
        code { background:#f1f5f9; padding:.1rem .35rem; border-radius:4px; font-size:.8125rem; }
        .empty { text-align:center; color:var(--muted); padding:2rem; }

        /* ── landing page ─────────────────────────────────────────── */
        .hero { background:#fff; border:1px solid var(--line); border-radius:14px;
                padding:1.75rem; margin-bottom:2.5rem; }
        .hero__lede { margin:0 0 1.25rem; font-size:1.0625rem; line-height:1.7; color:#334155; max-width:44rem; }
        .hero__lede strong { color:var(--ink); }
        .hero__cmd { display:flex; align-items:center; gap:.6rem; padding:.65rem .8rem;
                border:1px solid var(--line); border-radius:9px; background:#f8fafc; max-width:34rem; }
        .hero__cmd span { color:var(--accent); font-weight:700; }
        .hero__cmd code { flex:1; background:none; padding:0; font-size:.8125rem; overflow-x:auto; white-space:nowrap; }
        .hero__cmd button { border:1px solid var(--line); background:#fff; border-radius:6px;
                padding:.3rem .6rem; font-size:.6875rem; font-weight:700; letter-spacing:.05em;
                color:var(--muted); cursor:pointer; }
        .hero__cmd button:hover { color:var(--accent); border-color:var(--accent); }
        .hero__links { display:flex; flex-wrap:wrap; gap:1.25rem; margin-top:1.25rem; font-size:.875rem; }
        .hero__links a { color:var(--accent); text-decoration:none; }
        .hero__links a:hover { text-decoration:underline; }

        .sec { font-size:1.0625rem; letter-spacing:-.01em; margin:2.5rem 0 .3rem; }
        .sec__sub { margin:0 0 1rem; color:var(--muted); font-size:.875rem; line-height:1.6; max-width:46rem; }

        .cards { display:grid; grid-template-columns:1fr; gap:1rem; }
        @media (min-width:800px) { .cards { grid-template-columns:repeat(3,1fr); } }
        .card-link { display:flex; flex-direction:column; background:#fff; border:1px solid var(--line);
                border-radius:12px; padding:1.25rem; text-decoration:none; color:inherit;
                transition:border-color .15s, transform .15s; }
        .card-link:hover { border-color:var(--accent); transform:translateY(-2px); }
        .card-link h3 { margin:0 0 .5rem; font-size:1rem; }
        .card-link p { margin:0 0 1rem; font-size:.8125rem; line-height:1.6; color:var(--muted); flex:1; }
        .card-link__go { font-size:.8125rem; font-weight:600; color:var(--accent); }

        .facts { display:grid; grid-template-columns:1fr; gap:.75rem; }
        @media (min-width:800px) { .facts { grid-template-columns:repeat(2,1fr); } }
        .facts div { background:#fff; border:1px solid var(--line); border-radius:10px;
                padding:.9rem 1rem; font-size:.8125rem; line-height:1.6; color:var(--muted); }
        .facts strong { color:var(--ink); }

        /* ── "the code behind this page" ───────────────────────────── */
        .code-panel { margin-top:1.5rem; border:1px solid var(--line); border-radius:12px;
                background:#fff; overflow:hidden; }
        .code-panel > summary { cursor:pointer; padding:.85rem 1.1rem; font-size:.875rem;
                font-weight:600; display:flex; align-items:center; gap:.55rem;
                list-style:none; user-select:none; }
        .code-panel > summary::-webkit-details-marker { display:none; }
        .code-panel > summary:hover { background:#f8fafc; }
        .code-panel__chev { color:var(--accent); transition:transform .15s; display:inline-block; }
        .code-panel[open] .code-panel__chev { transform:rotate(90deg); }
        .code-panel__count { margin-left:auto; font-weight:400; color:var(--muted);
                font-size:.75rem; }
        .code-panel__body { border-top:1px solid var(--line); padding:1.1rem; background:#f8fafc; }
        .code-panel__intro { margin:0 0 1rem; font-size:.875rem; color:var(--muted); line-height:1.6; }
        .code-panel__intro strong { color:var(--ink); }
        .code-block { margin:0 0 1rem; border:1px solid var(--line); border-radius:9px;
                overflow:hidden; background:#282c34; }
        .code-block:last-child { margin-bottom:0; }
        .code-block figcaption { display:flex; align-items:center; gap:.6rem; flex-wrap:wrap;
                padding:.5rem .8rem; background:#21252b; border-bottom:1px solid #3a3f4b; }
        .code-block figcaption code { background:none; color:#abb2bf; font-size:.75rem; padding:0; }
        .code-block figcaption span { margin-left:auto; color:#7f8796; font-size:.6875rem; }
        .code-block pre { margin:0; max-height:22rem; overflow:auto; }
        .code-block pre code { display:block; background:none; border-radius:0; color:#abb2bf;
                font-size:.78rem; line-height:1.65; padding:1rem; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>@yield('heading', 'Advanced Filters')</h1>
        <p class="lede">
            <code>laratribe/laravel-advanced-filters</code> v1.0.0 · 80 products ·
            @yield('frontend', 'Blade + Alpine')
        </p>

        <nav>
            <a href="{{ route('demo.home') }}" @if(request()->routeIs('demo.home')) aria-current="page" @endif>Overview</a>
            <a href="{{ route('demo.blade') }}" @if(request()->routeIs('demo.blade')) aria-current="page" @endif>Blade + Alpine</a>
            <a href="{{ route('demo.livewire') }}" @if(request()->routeIs('demo.livewire')) aria-current="page" @endif>Livewire</a>
            <a href="{{ route('demo.api') }}" @if(request()->routeIs('demo.api')) aria-current="page" @endif>JSON API</a>
        </nav>

        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
