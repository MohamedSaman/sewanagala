<?php

namespace App\Livewire\Admin;

use Exception;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use App\Models\ProductStock;
use App\Models\ProductDetail;
use App\Livewire\Concerns\WithDynamicLayout;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

#[Title('Product Stock Details')]
class ProductStockDetails extends Component
{
    use WithDynamicLayout, WithPagination;
    public $search;
    public $siteFilter = '';
    public $perPage = 30;

    public function render()
    {
        $sites = ProductStock::whereNotNull('site')
            ->where('site', '!=', '')
            ->distinct()
            ->orderBy('site')
            ->pluck('site');

        $salesSummary = DB::table('sale_items')
            ->select('product_id', DB::raw('SUM(quantity) as sold_qty'))
            ->groupBy('product_id');

        $query = ProductDetail::join('product_stocks', 'product_details.id', '=', 'product_stocks.product_id')
            ->leftJoinSub($salesSummary, 'sales_summary', function ($join) {
                $join->on('product_details.id', '=', 'sales_summary.product_id');
            })
            ->join('brand_lists', 'product_details.brand_id', '=', 'brand_lists.id')
            ->select(
                'product_stocks.*',
                'product_details.name as Product_name',
                'brand_lists.brand_name as Product_brand',
                'product_details.model as Product_model',
                'product_details.code as Product_code',
                'product_details.image as Product_image',
                DB::raw('COALESCE(sales_summary.sold_qty, 0) as sold_qty'),
                DB::raw('(COALESCE(sales_summary.sold_qty, 0) + COALESCE(product_stocks.available_stock, 0) + COALESCE(product_stocks.damage_stock, 0)) as calculated_total')
            )
            ->where(function ($query) {
                $query->where('product_details.name', 'like', '%' . $this->search . '%')
                    ->orWhere('product_details.code', 'like', '%' . $this->search . '%');
            });

        if ($this->siteFilter !== '') {
            $query->where('product_stocks.site', $this->siteFilter);
        }

        $ProductStocks = $query->orderby('product_stocks.available_stock', 'desc')
            ->paginate($this->perPage);

        return view('livewire.admin.Product-stock-details', [
            'ProductStocks' => $ProductStocks,
            'sites' => $sites,
        ])->layout($this->layout);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSiteFilter()
    {
        $this->resetPage();
    }

    public function exportToCSV()
    {
        $salesSummary = DB::table('sale_items')
            ->select('product_id', DB::raw('SUM(quantity) as sold_qty'))
            ->groupBy('product_id');

        // Get data
        $query = ProductDetail::join('product_stocks', 'product_details.id', '=', 'product_stocks.product_id')
            ->leftJoinSub($salesSummary, 'sales_summary', function ($join) {
                $join->on('product_details.id', '=', 'sales_summary.product_id');
            })
            ->join('brand_lists', 'product_details.brand_id', '=', 'brand_lists.id')
            ->select(
                'product_details.name',
                'product_details.code',
                'brand_lists.brand_name as brand',
                'product_details.model',
                'product_stocks.site',
                DB::raw('(COALESCE(sales_summary.sold_qty, 0) + COALESCE(product_stocks.available_stock, 0) + COALESCE(product_stocks.damage_stock, 0)) as total_stock'),
                'product_stocks.available_stock',
                DB::raw('COALESCE(sales_summary.sold_qty, 0) as sold_qty'),
                'product_stocks.damage_stock'
            );

        if ($this->siteFilter !== '') {
            $query->where('product_stocks.site', $this->siteFilter);
        }

        $ProductStocks = $query->get();

        if ($ProductStocks->isEmpty()) {
            $this->dispatch('banner-message', [
                'style' => 'danger',
                'message' => 'No data available to export'
            ]);
            return;
        }

        // Generate filename with date
        $fileName = 'Product_stock_' . date('Y-m-d_His') . '.csv';

        // Create CSV content with headers
        $headers = [
            'Name',
            'Code',
            'Brand',
            'Model',
            'Site',
            'Total Stock',
            'Available Stock',
            'Sold Count',
            'Damage Stock'
        ];

        $callback = function () use ($ProductStocks, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);

            foreach ($ProductStocks as $stock) {
                $row = [
                    $stock->name ?? '-',
                    $stock->code ?? '-',
                    $stock->brand ?? '-',
                    $stock->model ?? '-',
                    $stock->site ?? 'Store',
                    $stock->total_stock ?? '0',
                    $stock->available_stock ?? '0',
                    $stock->sold_qty ?? '0',
                    $stock->damage_stock ?? '0'
                ];
                fputcsv($file, $row);
            }

            fclose($file);
        };

        // Create response with headers for browser download
        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ]);
    }
}
