<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotationPrintController extends Controller
{
    /**
     * Display printable view for Quotation (same layout & format switcher as sale invoice receipt)
     */
    public function printQuotation($id, Request $request)
    {
        $quotation = Quotation::with(['customer', 'creator'])->findOrFail($id);

        $paper = in_array(strtolower($request->query('paper', 'a5')), ['a4', 'a5', 'a5-on-a4', '80mm'])
            ? strtolower($request->query('paper', 'a5'))
            : 'a5';

        $viewOnly = $request->has('view_only');

        return view('components.quotation-receipt-print', compact('quotation', 'paper', 'viewOnly'));
    }

    /**
     * Download Quotation as PDF (supports A5 landscape and A4 portrait, same layout as sale invoice PDF)
     */
    public function downloadQuotation($id, Request $request)
    {
        $quotation = Quotation::with(['customer', 'creator'])->findOrFail($id);

        $paper = in_array(strtolower($request->query('paper', 'a5')), ['a4', 'a5'])
            ? strtolower($request->query('paper', 'a5'))
            : 'a5';

        $pdf = Pdf::loadView('admin.quotations.invoice-pdf', compact('quotation', 'paper'));

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
            'quotation-' . $quotation->quotation_number . ($paper === 'a4' ? '-a4' : '') . '.pdf'
        );
    }
}
