@extends('layout')
@section('title', 'JSON API — Advanced Filters')
@section('heading', 'JSON API')
@section('frontend', 'no Blade, no Alpine, no Livewire — just the wire contract')

@section('content')
    <div class="card">
        <p class="lede" style="margin:0 0 .75rem">
            The same engine with the UI removed. Useful for Inertia, a SPA, or a mobile client —
            you render the interface, the package validates and applies the filters.
        </p>
        <p style="margin:0"><strong>Try it:</strong></p>
        <ul style="font-size:.875rem; line-height:1.9">
            <li><a href="{{ route('api.filters') }}" target="_blank">GET /api/products/filters</a> — the column definitions your UI builds from</li>
            <li><a href="{{ route('api.products') }}" target="_blank">GET /api/products</a> — unfiltered, paginated</li>
            <li><a href="{{ route('api.products') }}?column_filters[0][field]=category&column_filters[0][operator]=in&column_filters[0][value][0]=books" target="_blank">…?category in [books]</a></li>
            <li><a href="{{ route('api.products') }}?column_filters[0][field]=price&column_filters[0][operator]=greater_than&column_filters[0][value]=200" target="_blank">…?price &gt; 200</a></li>
            <li><a href="{{ route('api.products') }}?column_filters[0][field]=sku&column_filters[0][operator]=is_empty" target="_blank">…?sku is_empty</a> — nullable() in action</li>
            <li><a href="{{ route('api.products') }}?column_filters[0][field]=secret&column_filters[0][operator]=equals&column_filters[0][value]=x" target="_blank">…?secret = x</a> — undeclared column, silently dropped</li>
        </ul>
    </div>

    <div class="card">
        <p style="margin:0 0 .5rem"><strong>A JSON body works too</strong> — <code>$request-&gt;input()</code> reads either source:</p>
<pre style="margin:0;background:#0f172a;color:#cbd5e1;padding:1rem;border-radius:8px;overflow-x:auto;font-size:.8125rem">curl -X POST {{ route('api.products') }} \
  -H 'Content-Type: application/json' \
  -d '{"column_filters":[{"field":"stock","operator":"between","value":10,"valueTo":40}]}'</pre>
    </div>

    @include('_code', [
        'intro' => 'Same model again, with the UI removed entirely. Two routes is the whole integration —
                    <strong>no Blade, no Alpine, no Livewire</strong>.',
        'files' => [
            ['path' => 'app/Models/Product.php', 'note' => 'identical on all three pages'],
            ['path' => 'routes/web.php', 'note' => 'the /api routes at the bottom'],
        ],
    ])
@endsection
