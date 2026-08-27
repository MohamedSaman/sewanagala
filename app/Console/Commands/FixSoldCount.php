<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixSoldCount extends Command
{
    protected $signature   = 'fix:sold-count';
    protected $description = 'Recalculate sold_count in product_stocks from actual sale_items and returns data';

    public function handle(): int
    {
        $stocks = DB::table('product_stocks')->get();

        $this->info("Fixing sold_count for {$stocks->count()} products...");

        foreach ($stocks as $stock) {
            $pid = $stock->product_id;

            $sold = (int) DB::table('sale_items')
                ->where('product_id', $pid)
                ->sum('quantity');

            $returned = (int) DB::table('returns_products')
                ->where('product_id', $pid)
                ->sum('return_quantity');

            $net = max(0, $sold - $returned);

            DB::table('product_stocks')
                ->where('product_id', $pid)
                ->update(['sold_count' => $net]);

            $this->line("  Product #{$pid}: sold={$sold}, returned={$returned}, net_sold={$net}");
        }

        $this->info("Done! sold_count values corrected successfully.");
        return Command::SUCCESS;
    }
}
