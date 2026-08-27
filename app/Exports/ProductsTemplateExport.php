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
            ['001A LAK-P WHITE', 'Inventory Part', 133, 616, '', 'pannala', 'WALL TILES', 'Revenue'],
            ['003B MAK (300X600)', 'Inventory Part', 20, 646, '', 'Sewanagala', 'WALL TILES', 'Revenue'],
        ];
    }

    /**
     * Return column headings
     */
    public function headings(): array
    {
        return [
            'Item Name', 'Item Type', 'on_hand', 'Cost', 'Vendor', 'Site', 'Item Category', 'Income Account',
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
