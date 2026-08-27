<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class StaffReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
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
        return collect(\Illuminate\Support\Facades\DB::table('users')
            ->where('role', 'staff')
            ->leftJoin('staff_sales', 'users.id', '=', 'staff_sales.staff_id')
            ->select(
                'users.name',
                'users.email',
                \Illuminate\Support\Facades\DB::raw('COALESCE(SUM(staff_sales.sold_value), 0) as total_sales'),
                \Illuminate\Support\Facades\DB::raw('COALESCE(SUM(staff_sales.sold_quantity), 0) as total_quantity')
            )
            ->groupBy('users.id', 'users.name', 'users.email')
            ->get());
    }

    public function title(): string
    {
        return 'Staff Sales Report';
    }

    public function headings(): array
    {
        return ['#', 'Staff Name', 'Email', 'Total Sales Value (Rs.)', 'Total Quantity Sold'];
    }

    public function map($row): array
    {
        static $index = 0;
        $index++;

        $row = (object) $row;
        return [
            $index,
            $row->name           ?? '',
            $row->email          ?? '',
            number_format((float) ($row->total_sales    ?? 0), 2),
            $row->total_quantity ?? 0,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
