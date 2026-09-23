<?php

namespace App\Livewire;

use App\Models\Product;
use Laratribe\AdvancedFilters\Support\FilteredQuery;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The event-driven integration: the package's panel owns the filter rows and dispatches
 * `advanced-filters-updated`; this component listens and re-queries. The two know nothing
 * about each other.
 */
class ProductResults extends Component
{
    use WithPagination;

    /** @var array<int, array<string, mixed>> */
    public array $filters = [];

    #[On('advanced-filters-updated')]
    public function updateFilters(array $filters): void
    {
        $this->filters = $filters;
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.product-results', [
            // Re-validated here too — a hand-crafted Livewire payload can't smuggle a column in.
            'products' => FilteredQuery::for(Product::class)->withFilters($this->filters)->paginate(15),
        ]);
    }
}
