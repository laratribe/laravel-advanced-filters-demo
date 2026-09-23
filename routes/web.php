<?php

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laratribe\AdvancedFilters\Support\FilteredQuery;

Route::get('/', fn () => view('home'))->name('demo.home');

// ── Blade + Alpine ───────────────────────────────────────────────────────────
Route::get('/blade', function (Request $request) {
    $query = FilteredQuery::for(Product::class)->fromRequest($request);

    return view('blade', [
        'products' => $query->paginate(15),
        'fields' => $query->filterDefinitions(),   // wire contract OUT
        'active' => $query->activeFilters(),      // normalised rows, for the chips
    ]);
})->name('demo.blade');

// ── Livewire ─────────────────────────────────────────────────────────────────
Route::get('/livewire', fn () => view('livewire-page'))->name('demo.livewire');

// ── JSON API (no UI at all) ──────────────────────────────────────────────────
Route::get('/api', fn () => view('api'))->name('demo.api');

Route::get('/api/products/filters', fn () => response()->json([
    'fields' => Product::filterDefinitions(),
]))->name('api.filters');

Route::match(['get', 'post'], '/api/products', fn (Request $request) => response()->json(
    FilteredQuery::for(Product::class)->fromRequest($request)->paginate(15)
))->name('api.products');
