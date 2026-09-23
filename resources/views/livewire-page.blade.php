@extends('layout')
@section('title', 'Livewire — Advanced Filters')
@section('heading', 'Livewire')
@section('frontend', 'no page reload — the panel dispatches, the table re-queries')

@push('head')
    @livewireStyles
    {{-- No CDN Alpine here: Livewire ships its own, and loading a second breaks both. --}}
    <script type="module">
        import '{{ asset('vendor/advanced-filters/advanced-filters.js') }}?v={{ filemtime(public_path('vendor/advanced-filters/advanced-filters.js')) }}'
    </script>
@endpush

@section('content')
    <div class="card">
        <livewire:advanced-filters::panel :model="\App\Models\Product::class" />
    </div>

    <livewire:product-results />

    @include('_code', [
        'intro' => 'Same model as the Blade page — <strong>not a line of filter logic changes</strong>.
                    The panel dispatches an event; the results component listens and re-queries.',
        'files' => [
            ['path' => 'app/Models/Product.php', 'note' => 'identical on all three pages'],
            ['path' => 'app/Livewire/ProductResults.php', 'note' => 'listens for the event'],
            ['path' => 'resources/views/livewire-page.blade.php', 'note' => 'this page'],
        ],
    ])
@endsection

@push('scripts')
    @livewireScripts
@endpush
