@extends('layout')
@section('title', 'Advanced Filters for Laravel — live demo')
@section('heading', 'Advanced Filters for Laravel')
@section('frontend', 'a live demo of laratribe/laravel-advanced-filters')

@section('content')
    <div class="hero">
        <p class="hero__lede">
            Declare filters <strong>once</strong> on an Eloquent model. The package publishes what
            can be filtered and how, so the same definition drives a filter UI in Blade, Livewire
            or Inertia — or no UI at all behind a JSON API.
        </p>

        <div class="hero__cmd">
            <span>$</span>
            <code id="install">composer require laratribe/laravel-advanced-filters</code>
            <button type="button" onclick="
                navigator.clipboard?.writeText(document.getElementById('install').textContent)
                    .then(() => { this.textContent = 'COPIED'; setTimeout(() => this.textContent = 'COPY', 1500) })
            ">COPY</button>
        </div>

        <div class="hero__links">
            <a href="https://laratribe.github.io/laravel-advanced-filters" target="_blank" rel="noreferrer">📖 Documentation</a>
            <a href="https://github.com/laratribe/laravel-advanced-filters" target="_blank" rel="noreferrer">⌨ GitHub</a>
            <a href="https://packagist.org/packages/laratribe/laravel-advanced-filters" target="_blank" rel="noreferrer">📦 Packagist</a>
        </div>
    </div>

    <h2 class="sec">This is the whole setup</h2>
    <p class="sec__sub">
        Every page in this demo is powered by this one method. Nothing else declares a filter.
    </p>

    @include('_code', [
        'title' => 'The filter definition',
        'open' => true,
        'files' => [
            ['path' => 'app/Models/Product.php', 'note' => 'the only place filters are declared'],
        ],
    ])

    <h2 class="sec">Three frontends, one definition</h2>
    <p class="sec__sub">
        Each page below filters the same 80 products through the same model. Open
        <em>“The code behind this page”</em> on any of them — the model never changes.
    </p>

    <div class="cards">
        <a class="card-link" href="{{ route('demo.blade') }}">
            <h3>Blade + Alpine</h3>
            <p>The packaged panel with no build step. Alpine from a CDN, package CSS and JS published to <code>public/</code>. Applying a filter reloads with the rows in the query string.</p>
            <span class="card-link__go">Open demo →</span>
        </a>

        <a class="card-link" href="{{ route('demo.livewire') }}">
            <h3>Livewire</h3>
            <p>No page reload. The panel owns the filter rows and dispatches an event; a separate results component listens and re-queries. Neither knows about the other.</p>
            <span class="card-link__go">Open demo →</span>
        </a>

        <a class="card-link" href="{{ route('demo.api') }}">
            <h3>JSON API</h3>
            <p>No Blade, no Alpine, no Livewire — just the wire contract. For Inertia, a SPA or a mobile client, where you render the interface yourself.</p>
            <span class="card-link__go">Open demo →</span>
        </a>
    </div>

    <h2 class="sec">What this demo shows</h2>
    <div class="facts">
        <div><strong>Text, set, number and date</strong> filters, including a nullable SKU so <code>is_empty</code> has something to find.</div>
        <div><strong>Category is <code>multiple()</code></strong> and status is not — so you can see the OR multi-select next to a single select.</div>
        <div><strong>Filters survive paging</strong>, because the active rows travel in the query string.</div>
        <div><strong>Undeclared columns are dropped.</strong> Try <code>?column_filters[0][field]=secret</code> — the filter engine ignores it rather than erroring.</div>
    </div>
@endsection
