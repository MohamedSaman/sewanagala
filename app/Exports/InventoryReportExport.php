<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $data;
    protected $total;

    public function __construct($data = null, $total = 0)
    {
        $this->data  = $data;
        $this->total = $total;
    }

    public function collection()
    {
        if ($this->data) {
            return collect($this->data);
        }
        return collect(\Illuminate\Support\Facades\DB::table('product_details')
            ->join('product_stocks', 'product_details.id', '=', 'product_stocks.product_id')
            ->join('brand_lists', 'product_details.brand_id', '=', 'brand_lists.id')
            ->select(
                'product_details.name',
                'product_details.model',
                'brand_lists.brand_name as brand',
                'product_stocks.total_stock',
                'product_stocks.available_stock',
                'product_stocks.sold_count',
                'product_stocks.damage_stock'
            )
            ->get());
    }

    public function title(): string
    {
        return 'Inventory Report';
    }

    public function headings(): array
    {
        return ['#', 'Product Name', 'Model', 'Brand', 'Total Stock', 'Available Stock', 'Sold Count', 'Damaged Stock'];
    }

    public function map($row): array
    {
        static $index = 0;
        $index++;

        $row = (object) $row;
        return [
            $index,
            $row->name            ?? '',
            $row->model           ?? '',
            $row->brand           ?? '',
            $row->total_stock     ?? 0,
            $row->available_stock ?? 0,
            $row->sold_count      ?? 0,
            $row->damage_stock    ?? 0,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
