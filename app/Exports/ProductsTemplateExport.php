<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductsTemplateExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    /**
     * Return sample data for the template
     */
    public function array(): array
    {
        return [
            ['SE0001', '001A LAK-P WHITE', 133, 616, 750, 'pannala', 'WALL TILES', 'Revenue'],
            ['SE0002', '003B MAK (300X600)', 20, 646, 800, 'Sewanagala', 'WALL TILES', 'Revenue'],
        ];
    }

    /**
     * Return column headings
     */
    public function headings(): array
    {
        return [
            'code', 'Item Name', 'on_hand', 'Cost', 'Selling Price', 'Site', 'Item Category', 'Income Account',
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row (headers)
            1 => [
                'font' => [
                    'bold' => true,
                    'size' => 12,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4CAF50']
                ],
            ],
        ];
    }

    /**
     * Return the worksheet title
     */
    public function title(): string
    {
        return 'Products Import Template';
    }
}
