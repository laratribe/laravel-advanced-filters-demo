# Laravel Advanced Filters — Demo

[![Package](https://img.shields.io/packagist/v/laratribe/laravel-advanced-filters.svg?label=laratribe/laravel-advanced-filters)](https://packagist.org/packages/laratribe/laravel-advanced-filters)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://opensource.org/licenses/MIT)

A runnable Laravel application that demonstrates
**[laratribe/laravel-advanced-filters](https://github.com/laratribe/laravel-advanced-filters)** —
declare filters **once** on an Eloquent model, then render them with **Blade + Alpine**,
**Livewire**, or no UI at all as a **JSON API**.

🚀 **[This app, running live →](https://advanced-filters.laratribe.com)** — no install needed.

📖 **[Package documentation](https://laratribe.github.io/laravel-advanced-filters)** ·
📦 **[Packagist](https://packagist.org/packages/laratribe/laravel-advanced-filters)** ·
⌨ **[Package source](https://github.com/laratribe/laravel-advanced-filters)**

Every page in this app filters the same 80 seeded products through the same model. The
filter definition never changes between them — only the last few lines of each route do.

---

## What's inside

| Route | Frontend | What it shows |
|-------|----------|---------------|
| `/` | — | The filter definition, and links to the three demos |
| `/blade` | Blade + Alpine | The packaged panel with **no build step**. Alpine from a CDN, package CSS/JS served from `public/`. Applying a filter reloads with the rows in the query string. |
| `/livewire` | Livewire 4 | No page reload. The panel owns the filter rows and dispatches `advanced-filters-updated`; a separate results component listens and re-queries. Neither knows about the other. |
| `/api` | JSON API | No Blade, no Alpine, no Livewire — just the wire contract, for Inertia, a SPA or a mobile client. |

Each demo page has a **“The code behind this page”** section that shows the actual source
files it runs on, so you can read the integration next to the thing it produces.

## Requirements

- **PHP 8.3+**
- **SQLite** (the default — no database server to configure)
- Composer

Node is **not** required. The demo pages ship their own CSS inline and load the package
assets from `public/vendor/advanced-filters/`, so there is nothing to build.

## Quick start

```bash
git clone git@github.com:laratribe/laravel-advanced-filters-demo.git
cd laravel-advanced-filters-demo

composer install
cp .env.example .env
php artisan key:generate

touch database/database.sqlite
php artisan migrate

php artisan serve
```

Then open **http://localhost:8000**.

The 80 demo products are inserted by the `create_products_table` migration itself, so
`migrate` is all the seeding you need. To start over: `php artisan migrate:fresh`.

> The package's published assets are already committed under
> `public/vendor/advanced-filters/`. If you ever need to refresh them:
> `php artisan vendor:publish --tag=advanced-filters-assets`

## The whole setup

This is the only place a filter is declared. All three frontends read from it.

```php
// app/Models/Product.php

class Product extends Model implements Filterable
{
    use HasFilters;

    public static function filters(): array
    {
        return [
            TextFilter::make('name', 'Name'),
            TextFilter::make('sku', 'SKU')->nullable(),
            SetFilter::make('category', 'Category')
                ->options([
                    'electronics' => 'Electronics',
                    'books' => 'Books',
                    'clothing' => 'Clothing',
                    'toys' => 'Toys',
                ])
                ->multiple(),
            SetFilter::make('status', 'Status')->options([
                'in_stock' => 'In stock',
                'low' => 'Low stock',
                'out' => 'Out of stock',
            ]),
            NumericFilter::make('price', 'Price'),
            NumericFilter::make('stock', 'Stock'),
            DateFilter::make('released_at', 'Released'),
        ];
    }
}
```

`filters()` is also the **allow-list**: anything not declared here can never reach the
query, whatever the request says.

## How each frontend wires up

**Blade + Alpine** — one route, one component:

```php
// routes/web.php
Route::get('/blade', function (Request $request) {
    $query = FilteredQuery::for(Product::class)->fromRequest($request);

    return view('blade', [
        'products' => $query->paginate(15),
        'fields'   => $query->filterDefinitions(),  // wire contract OUT
        'active'   => $query->activeFilters(),      // normalised rows, for the chips
    ]);
});
```

```blade
{{-- resources/views/blade.blade.php --}}
<x-advanced-filters::panel :fields="$fields" :active="$active" :base-url="route('demo.blade')" />
```

**Livewire** — the panel and the results talk through an event, not through each other:

```php
// app/Livewire/ProductResults.php
#[On('advanced-filters-updated')]
public function updateFilters(array $filters): void
{
    $this->filters = $filters;
    $this->resetPage();
}

public function render()
{
    return view('livewire.product-results', [
        // Re-validated here too — a hand-crafted payload can't smuggle a column in.
        'products' => FilteredQuery::for(Product::class)->withFilters($this->filters)->paginate(15),
    ]);
}
```

**JSON API** — two routes are the entire integration:

```php
Route::get('/api/products/filters', fn () => response()->json([
    'fields' => Product::filterDefinitions(),
]));

Route::match(['get', 'post'], '/api/products', fn (Request $request) => response()->json(
    FilteredQuery::for(Product::class)->fromRequest($request)->paginate(15)
));
```

Filters travel as `column_filters` in the query string:

```
/api/products?column_filters[0][field]=category&column_filters[0][operator]=in&column_filters[0][value][0]=books
/api/products?column_filters[0][field]=price&column_filters[0][operator]=greater_than&column_filters[0][value]=200
/api/products?column_filters[0][field]=sku&column_filters[0][operator]=is_empty
```

A JSON body works too — `$request->input()` reads either source:

```bash
curl -X POST http://localhost:8000/api/products \
  -H 'Content-Type: application/json' \
  -d '{"column_filters":[{"field":"stock","operator":"between","value":10,"valueTo":40}]}'
```

## Things worth trying

- **Undeclared columns are dropped.** Add `?column_filters[0][field]=secret&column_filters[0][operator]=equals&column_filters[0][value]=x`
  to any demo URL — the engine ignores it rather than erroring.
- **`is_empty` has something to find.** Every 7th product has a `null` SKU, because `sku`
  is declared `->nullable()`.
- **Multi-select next to single select.** `category` is `->multiple()` (OR across values),
  `status` is not.
- **Filters survive paging** — the active rows travel in the query string.

## Project map

| Path | |
|------|--|
| [app/Models/Product.php](app/Models/Product.php) | The filter definition — the whole setup |
| [routes/web.php](routes/web.php) | All four routes, ~30 lines |
| [app/Livewire/ProductResults.php](app/Livewire/ProductResults.php) | The event-driven Livewire integration |
| [resources/views/blade.blade.php](resources/views/blade.blade.php) | The Blade + Alpine page |
| [resources/views/livewire/product-results.blade.php](resources/views/livewire/product-results.blade.php) | The Livewire results table |
| [database/migrations/2026_09_22_000000_create_products_table.php](database/migrations/2026_09_22_000000_create_products_table.php) | Schema + the 80 seeded rows |

## 👤 Author

**Ram Sharma**

- GitHub: [@rnsharma93](https://github.com/rnsharma93)
- Email: rns6393@gmail.com

If you find the package useful, please consider starring
[the repository](https://github.com/laratribe/laravel-advanced-filters) on GitHub!

## 📜 License

This demo application, like the package it demonstrates, is open-source software licensed
under the [MIT license](https://opensource.org/licenses/MIT).

Issues and feature requests for the package itself belong on the
[package issues page](https://github.com/laratribe/laravel-advanced-filters/issues).
