<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->string('category');
            $table->string('status');
            $table->decimal('price', 10, 2);
            $table->integer('stock');
            $table->date('released_at');
        });

        $categories = ['electronics', 'books', 'clothing', 'toys'];
        $statuses = ['in_stock', 'low', 'out'];
        $words = ['Nimbus', 'Vertex', 'Lumen', 'Atlas', 'Orbit', 'Cobalt', 'Ember', 'Quartz'];

        $rows = [];
        for ($i = 1; $i <= 80; $i++) {
            $rows[] = [
                'name' => $words[$i % 8].' '.['Pro', 'Mini', 'Max', 'Lite'][$i % 4].' '.$i,
                // A few nulls so the `is_empty` / `is_not_empty` clauses have something to find.
                'sku' => $i % 7 === 0 ? null : 'SKU-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'category' => $categories[$i % 4],
                'status' => $statuses[$i % 3],
                'price' => round(4.5 + ($i * 3.7), 2),
                'stock' => ($i * 13) % 140,
                'released_at' => now()->subDays(($i * 11) % 400)->toDateString(),
            ];
        }
        DB::table('products')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
