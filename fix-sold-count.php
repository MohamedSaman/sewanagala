<?php

use Illuminate\Support\Facades\DB;

// Fix stale sold_count: recalculate by summing sale_items quantity 
// for each product, then subtracting returned quantities.

$productStocks = DB::table('product_stocks')->get();

foreach ($productStocks as $stock) {
    $productId = $stock->product_id;

    // Total units ever sold (across all sale_types)
    $totalSold = DB::table('sale_items')
        ->where('product_id', $productId)
        ->sum('quantity');

    // Total units returned
    $totalReturned = DB::table('returns_products')
        ->where('product_id', $productId)
        ->sum('return_quantity');

    // Net sold = sold - returned (minimum 0)
    $netSold = max(0, $totalSold - $totalReturned);

    DB::table('product_stocks')
        ->where('product_id', $productId)
        ->update(['sold_count' => $netSold]);

    echo "Product #{$productId}: sold={$totalSold}, returned={$totalReturned}, net_sold={$netSold}\n";
}

echo "\nDone! All sold_count values have been corrected.\n";
