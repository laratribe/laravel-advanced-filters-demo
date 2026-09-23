<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laratribe\AdvancedFilters\Concerns\HasFilters;
use Laratribe\AdvancedFilters\Contracts\Filterable;
use Laratribe\AdvancedFilters\Filters\{DateFilter, NumericFilter, SetFilter, TextFilter};

class Product extends Model implements Filterable
{
    use HasFilters;

    protected $guarded = [];

    public $timestamps = false;

    protected function casts(): array
    {
        return ['price' => 'float', 'stock' => 'integer', 'released_at' => 'date'];
    }

    /**
     * The one place that decides what can be filtered — and therefore the allow-list.
     * Anything not declared here can never reach the query, whatever the request says.
     */
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
