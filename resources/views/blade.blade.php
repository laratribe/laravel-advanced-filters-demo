@extends('layout')
@section('title', 'Blade + Alpine — Advanced Filters')
@section('heading', 'Blade + Alpine')
@section('frontend', 'zero build step — Alpine from a CDN, package JS published to public/')

@push('head')
    <script type="module">
        import Alpine from 'https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/module.esm.js'
        import '{{ asset('vendor/advanced-filters/advanced-filters.js') }}?v={{ filemtime(public_path('vendor/advanced-filters/advanced-filters.js')) }}'
        window.Alpine = Alpine
        Alpine.start()
    </script>
@endpush

@section('content')
    <div class="card">
        <x-advanced-filters::panel :fields="$fields" :active="$active" :base-url="route('demo.blade')" />
    </div>

    @include('_table')

    @include('_code', [
        'intro' => 'The model below is the <strong>same file</strong> the Livewire and JSON API pages use.
                    Only the last few lines differ per page — the filtering itself is declared once.',
        'files' => [
            ['path' => 'app/Models/Product.php', 'note' => 'identical on all three pages'],
            ['path' => 'routes/web.php', 'note' => 'the /blade route'],
            ['path' => 'resources/views/blade.blade.php', 'note' => 'this page'],
        ],
    ])
@endsection
