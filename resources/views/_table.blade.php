<table>
    <thead>
        <tr>
            <th>Name</th><th>SKU</th><th>Category</th><th>Status</th>
            <th class="num">Price</th><th class="num">Stock</th><th>Released</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($products as $p)
            <tr wire:key="p-{{ $p->id }}">
                <td>{{ $p->name }}</td>
                <td>{{ $p->sku ?? '—' }}</td>
                <td><span class="pill">{{ ucfirst($p->category) }}</span></td>
                <td>{{ str_replace('_', ' ', ucfirst($p->status)) }}</td>
                <td class="num">{{ number_format($p->price, 2) }}</td>
                <td class="num">{{ $p->stock }}</td>
                <td>{{ $p->released_at->format('j M Y') }}</td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">No products match the current filters.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="meta">
    <span>
        @if ($products->total())
            Showing <strong>{{ $products->firstItem() }}–{{ $products->lastItem() }}</strong>
            of <strong>{{ $products->total() }}</strong>
        @else
            <strong>0</strong> matched
        @endif
    </span>

    {{ $products->links('pagination') }}
</div>
