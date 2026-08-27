<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected $data;
    protected $total;
    protected $type;

    public function __construct($data = null, $total = 0, $type = 'sales')
    {
        $this->data  = $data;
        $this->total = $total;
        $this->type  = $type ?? 'sales';
    }

    public function collection()
    {
        if ($this->data) {
            return collect($this->data);
        }
        return \App\Models\Sale::with('customer')->get();
    }

    public function title(): string
    {
        return 'Sales Report';
    }

    public function headings(): array
    {
        // Daily-sales report has date, day_name, grand_total etc.
        if ($this->type === 'daily-sales') {
            return ['#', 'Date', 'Day', 'Total Sales (Count)', 'Gross Total (Rs.)', 'Return Total (Rs.)', 'Net Total (Rs.)'];
        }
        // Monthly-sales report
        if ($this->type === 'monthly-sales') {
            return ['#', 'Month', 'Year', 'Total Sales (Count)', 'Gross Total (Rs.)', 'Return Total (Rs.)', 'Net Total (Rs.)'];
        }
        // Standard sales report
        return ['#', 'Invoice Number', 'Customer Name', 'Sale Date', 'Total Amount (Rs.)', 'Due Amount (Rs.)', 'Payment Status', 'Sale Type'];
    }

    public function map($sale): array
    {
        static $index = 0;
        $index++;

        if ($this->type === 'daily-sales') {
            $gross  = (float) ($sale->grand_total ?? 0);
            $ret    = (float) ($sale->return_total ?? 0);
            return [
                $index,
                $sale->sale_date ?? '',
                $sale->day_name  ?? '',
                $sale->total_sales ?? 0,
                number_format($gross, 2),
                number_format($ret, 2),
                number_format($gross - $ret, 2),
            ];
        }

        if ($this->type === 'monthly-sales') {
            $gross = (float) ($sale->grand_total ?? 0);
            $ret   = (float) ($sale->return_total ?? 0);
            return [
                $index,
                $sale->month_name ?? '',
                $sale->year       ?? '',
                $sale->total_sales ?? 0,
                number_format($gross, 2),
                number_format($ret, 2),
                number_format($gross - $ret, 2),
            ];
        }

        // Standard invoice row
        $sale = (object) $sale; // handle both object & array
        return [
            $index,
            $sale->invoice_number ?? '',
            isset($sale->customer) ? ($sale->customer->name ?? 'Walk-in') : 'Walk-in',
            isset($sale->created_at) ? \Carbon\Carbon::parse($sale->created_at)->format('d/m/Y') : '',
            number_format((float) ($sale->total_amount ?? 0), 2),
            number_format((float) ($sale->due_amount  ?? 0), 2),
            ucfirst($sale->payment_status ?? ''),
            strtoupper($sale->sale_type ?? ''),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
