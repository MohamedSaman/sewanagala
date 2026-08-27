<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalaryReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
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
        return collect(\App\Models\User::where('role', 'staff')
            ->join('salaries', 'users.id', '=', 'salaries.user_id')
            ->select('users.name', 'salaries.net_salary', 'salaries.salary_month', 'salaries.payment_status')
            ->get());
    }

    public function title(): string
    {
        return 'Salary Report';
    }

    public function headings(): array
    {
        return ['#', 'Staff Name', 'Salary Month', 'Net Salary (Rs.)', 'Payment Status'];
    }

    public function map($row): array
    {
        static $index = 0;
        $index++;

        $row = (object) $row;
        return [
            $index,
            $row->name            ?? '',
            $row->salary_month    ?? '',
            number_format((float) ($row->net_salary ?? 0), 2),
            ucfirst($row->payment_status ?? ''),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
