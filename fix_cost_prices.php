<?php

use App\Models\SaleItem;
use App\Models\ReturnsProduct;
use App\Models\ProductBatch;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

DB::transaction(function() {
    // 1. Update sale_items
    $saleItems = SaleItem::all();
    foreach ($saleItems as $item) {
        // Find the batch that existed at or before the time of sale
        $batch = ProductBatch::where('product_id', $item->product_id)
            ->where('created_at', '<=', $item->created_at)
            ->orderBy('created_at', 'desc')
            ->first();

        // If no prior batch found, fall back to the first ever batch for this product
        if (!$batch) {
            $batch = ProductBatch::where('product_id', $item->product_id)
                ->orderBy('created_at', 'asc')
                ->first();
        }

        if ($batch) {
            $item->cost_price = $batch->supplier_price;
            $item->save();
        }
    }

    // 2. Update returns_products
    $returns = ReturnsProduct::all();
    foreach ($returns as $return) {
        // Find the corresponding sale item to match the cost price
        $saleItem = SaleItem::where('sale_id', $return->sale_id)
            ->where('product_id', $return->product_id)
            ->first();

        if ($saleItem) {
            $return->cost_price = $saleItem->cost_price;
            $return->save();
        } else {
            // Fallback similar to sale_items if no exact sale item found (should be rare)
            $batch = ProductBatch::where('product_id', $return->product_id)
                ->where('created_at', '<=', $return->created_at)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$batch) {
                $batch = ProductBatch::where('product_id', $return->product_id)
                    ->orderBy('created_at', 'asc')
                    ->first();
            }

            if ($batch) {
                $return->cost_price = $batch->supplier_price;
                $return->save();
            }
        }
    }
});

echo "Backfilled cost prices based on historical batch data successfully.\n";
