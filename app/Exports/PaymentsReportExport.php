<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * PaymentsReportExport
 *
 * The payments report returns an array with two keys: 'customer' and 'supplier'.
 * We flatten them into one sheet with a Type column.
 */
class PaymentsReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
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
        if ($this->data && is_array($this->data)) {
            // Merge customer & supplier payments into one flat collection
            $customer = collect($this->data['customer'] ?? []);
            $supplier = collect($this->data['supplier'] ?? []);
            return $customer->merge($supplier);
        }
        return \App\Models\Payment::all();
    }

    public function title(): string
    {
        return 'Payments Report';
    }

    public function headings(): array
    {
        return [
            '#',
            'Payment Date',
            'Amount (Rs.)',
            'Payment Method',
            'Customer / Supplier',
            'Invoice / Order',
            'Reference',
        ];
    }

    public function map($row): array
    {
        static $index = 0;
        $index++;

        $row    = (object) $row;
        $amount = (float) ($row->amount ?? 0);

        // Resolve customer/supplier name
        $party = '–';
        if (isset($row->sale) && $row->sale) {
            $sale  = is_object($row->sale) ? $row->sale : (object) $row->sale;
            $party = $sale->customer->name ?? 'Walk-in';
            $ref   = $sale->invoice_number ?? '';
        } elseif (isset($row->purchase_order) && $row->purchase_order) {
            $po    = is_object($row->purchase_order) ? $row->purchase_order : (object) $row->purchase_order;
            $party = $po->supplier->company_name ?? ($po->supplier->name ?? '–');
            $ref   = $po->order_number ?? '';
        } else {
            $ref = $row->reference ?? '';
        }

        return [
            $index,
            $row->payment_date   ?? '',
            number_format($amount, 2),
            ucfirst($row->payment_method ?? ''),
            $party,
            $ref ?? '',
            $row->notes ?? '',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
