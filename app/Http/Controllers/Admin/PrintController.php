<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PrintController extends Controller
{
    public function printSale($id, Request $request)
    {
        // Load sale with all necessary relationships including returns
        $sale = Sale::with(['customer', 'items.product', 'payments', 'returns' => function ($q) {
            $q->with('product');
        }])->findOrFail($id);

        $paper = in_array(strtolower($request->query('paper', 'a5')), ['a4', 'a5']) ? strtolower($request->query('paper', 'a5')) : 'a5';
        $viewOnly = $request->has('view_only');

        // Return the print view
        return view('components.sale-receipt-print', compact('sale', 'paper', 'viewOnly'));
    }

    public function downloadSale($id, Request $request)
    {
        $sale = Sale::with(['customer', 'user', 'items.product', 'payments', 'returns' => function ($q) {
            $q->with('product');
        }])->findOrFail($id);

        $paper = in_array(strtolower($request->query('paper', 'a5')), ['a4', 'a5']) ? strtolower($request->query('paper', 'a5')) : 'a5';

        $pdf = PDF::loadView('admin.sales.invoice', compact('sale', 'paper'));
        if ($paper === 'a4') {
            $pdf->setPaper('a4', 'portrait');
        } else {
            $pdf->setPaper('a5', 'landscape');
        }
        $pdf->setOption('dpi', 96);
        $pdf->setOption('defaultFont', 'sans-serif');

        return response()->streamDownload(
            function () use ($pdf) {
                echo $pdf->output();
            },
            'invoice-' . $sale->invoice_number . ($paper === 'a4' ? '-a4' : '') . '.pdf'
        );
    }
}
