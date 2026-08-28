<?php

namespace App\Imports;

use App\Models\BrandList;
use App\Models\CategoryList;
use App\Models\ProductBatch;
use App\Models\ProductDetail;
use App\Models\ProductPrice;
use App\Models\ProductStock;
use App\Models\ProductSupplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/** Imports the client's stock sheet (Item Name, on_hand, Cost, Vendor, Site, Item Category). */
class ProductsImport implements ToModel, WithHeadingRow, WithValidation, SkipsEmptyRows, SkipsOnFailure
{
    use SkipsFailures;

    private int $successCount = 0;
    private int $skipCount = 0;
    private int $defaultBrandId;
    private int $defaultSupplierId;

    public function __construct()
    {
        $this->defaultBrandId = BrandList::firstOrCreate(['brand_name' => 'Default Brand'], ['status' => 'active'])->id;
        $this->defaultSupplierId = ProductSupplier::firstOrCreate(
            ['name' => 'Default Supplier'],
            ['phone' => '0000000000', 'email' => 'default@supplier.com', 'address' => 'Default Address', 'status' => 'active']
        )->id;
    }

    public function model(array $row)
    {
        $name = trim((string) ($row['item_name'] ?? $row['name'] ?? ''));
        if ($name === '') { $this->skipCount++; return null; }

        $code = trim((string) ($row['code'] ?? ''));
        if ($code === '') {
            $code = strtoupper((string) Str::of($name)->before(' ')->replaceMatches('/[^A-Za-z0-9-]/', ''));
        }
        if ($code === '') { $code = 'IMP-' . strtoupper(Str::random(8)); }
        $baseCode = $code;
        $suffix = 1;
        while (ProductDetail::where('code', $code)->exists()) { $code = $baseCode . '-' . $suffix++; }

        $categoryName = trim((string) ($row['item_category'] ?? $row['category'] ?? '')) ?: 'Default Category';
        $category = CategoryList::firstOrCreate(['category_name' => $categoryName], ['status' => 'active']);
        $supplierName = trim((string) ($row['vendor'] ?? $row['supplier'] ?? '')) ?: 'Default Supplier';
        $supplier = $supplierName === 'Default Supplier' ? ProductSupplier::find($this->defaultSupplierId) :
            ProductSupplier::firstOrCreate(['name' => $supplierName], ['status' => 'active']);
        $stock = max(0, (int) round((float) str_replace(',', '', (string) ($row['on_hand'] ?? $row['stock'] ?? 0))));
        $cost = max(0, (float) str_replace(',', '', (string) ($row['cost'] ?? $row['supplier_price'] ?? 0)));
        $sellingPriceRaw = $row['selling_price'] ?? $row['sellingprice'] ?? $row['price'] ?? $row['sell_price'] ?? $row['selling'] ?? null;
        $sellingPrice = ($sellingPriceRaw !== null && trim((string)$sellingPriceRaw) !== '')
            ? max(0, (float) str_replace(',', '', (string) $sellingPriceRaw))
            : $cost;
        $site = trim((string) ($row['site'] ?? '')) ?: 'Store';

        DB::transaction(function () use ($name, $code, $category, $supplier, $stock, $cost, $sellingPrice, $site) {
            $product = ProductDetail::create([
                'code' => $code, 'name' => $name, 'status' => 'active',
                'brand_id' => $this->defaultBrandId, 'category_id' => $category->id,
                'supplier_id' => $supplier->id, 'site' => $site,
            ]);
            ProductPrice::create(['product_id' => $product->id, 'supplier_price' => $cost, 'selling_price' => $sellingPrice, 'discount_price' => 0]);
            ProductStock::create(['product_id' => $product->id, 'available_stock' => $stock, 'damage_stock' => 0, 'total_stock' => $stock, 'restocked_quantity' => $stock]);
            if ($stock > 0) {
                ProductBatch::create([
                    'product_id' => $product->id, 'batch_number' => ProductBatch::generateBatchNumber($product->id),
                    'supplier_price' => $cost, 'selling_price' => $sellingPrice, 'quantity' => $stock,
                    'remaining_quantity' => $stock, 'received_date' => now(), 'status' => 'active',
                ]);
            }
        });
        $this->successCount++;
        return null;
    }

    public function rules(): array
    {
        return [
            'item_name' => 'nullable|string|max:255',
            'on_hand' => 'nullable|numeric',
            'cost' => 'nullable|numeric',
            'selling_price' => 'nullable|numeric',
        ];
    }

    public function getSuccessCount(): int { return $this->successCount; }
    public function getSkipCount(): int { return $this->skipCount; }
    public function headingRow(): int { return 1; }
}
