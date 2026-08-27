<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceReportExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
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
        return collect(\Illuminate\Support\Facades\DB::table('attendances')
            ->join('users', 'attendances.user_id', '=', 'users.id')
            ->select('users.name', 'attendances.date', 'attendances.check_in', 'attendances.check_out', 'attendances.status')
            ->orderBy('attendances.date', 'desc')
            ->get());
    }

    public function title(): string
    {
        return 'Attendance Report';
    }

    public function headings(): array
    {
        return ['#', 'Staff Name', 'Date', 'Check In', 'Check Out', 'Status'];
    }

    public function map($row): array
    {
        static $index = 0;
        $index++;

        $row = (object) $row;
        return [
            $index,
            $row->name      ?? '',
            $row->date      ?? '',
            $row->check_in  ?? '',
            $row->check_out ?? '',
            ucfirst($row->status ?? ''),
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}
