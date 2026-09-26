<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation - {{ $quotation->quotation_number }}</title>
    @php
        $paper = isset($paper) && in_array(strtolower($paper), ['a4', 'a5', 'a5-on-a4', '80mm']) ? strtolower($paper) : 'a5';
        $viewOnly = isset($viewOnly) ? (bool)$viewOnly : false;

        // Customer Details
        $billName = trim((string) (optional($quotation->customer)->name ?? $quotation->customer_name ?? ''));
        $billAddress = trim((string) (optional($quotation->customer)->address ?? $quotation->customer_address ?? ''));
        $billPhone = trim((string) (optional($quotation->customer)->phone ?? $quotation->customer_phone ?? ''));
        if ($billName === '' || is_numeric($billName)) {
            $billName = 'Walking Customer';
        }

        // Quotation Date & Dynamic Validity Calculation
        $quotationDate = \Carbon\Carbon::parse($quotation->quotation_date ?: ($quotation->created_at ?: now()))->startOfDay();
        if (!empty($quotation->valid_until)) {
            $validUntil = \Carbon\Carbon::parse($quotation->valid_until)->startOfDay();
            $diffDays = (int) round($quotationDate->diffInDays($validUntil, false));
            $validityDays = $diffDays > 0 ? $diffDays : 14;
            if ($diffDays <= 0) {
                $validUntil = $quotationDate->copy()->addDays(14);
            }
        } else {
            $validityDays = 14;
            $validUntil = $quotationDate->copy()->addDays(14);
        }

        // Sales Rep / Prepared By
        $repName = optional($quotation->creator)->name ?? '-';

        // Decode and normalize items
        $rawItems = $quotation->items;
        if (is_string($rawItems)) {
            $rawItems = json_decode($rawItems, true);
        }
        $items = collect($rawItems ?? [])->map(function ($it, $index) {
            $unitPrice = (float)($it['unit_price'] ?? $it['price'] ?? 0);
            $qty = (float)($it['quantity'] ?? 1);
            $disc = (float)($it['discount_per_unit'] ?? $it['discount'] ?? 0);
            $total = isset($it['total']) ? (float)$it['total'] : (($unitPrice - $disc) * $qty);

            return (object)[
                'id' => $it['id'] ?? ($index + 1),
                'product_id' => $it['product_id'] ?? null,
                'product_name' => $it['product_name'] ?? $it['name'] ?? 'N/A',
                'product_code' => $it['product_code'] ?? $it['code'] ?? '',
                'product_model' => $it['product_model'] ?? $it['model'] ?? '',
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'discount_per_unit' => $disc,
                'total' => $total,
            ];
        })->values();

        $totalItemsCount = $items->count();

        // Calculations
        $calculatedSubtotal = $items->sum(fn($it) => $it->quantity * $it->unit_price);
        $itemDiscountTotal = $items->sum(fn($it) => $it->quantity * $it->discount_per_unit);
        $additionalDiscount = (float)($quotation->additional_discount ?? 0);
        $totalDiscount = (float)($quotation->discount_amount ?? ($itemDiscountTotal + $additionalDiscount));
        $displayDiscount = max(0, $totalDiscount);

        $subTotal = (float)($quotation->subtotal ?? $calculatedSubtotal);
        if ($subTotal <= 0) {
            $subTotal = $calculatedSubtotal;
        }
        $netTotal = (float)($quotation->total_amount ?? ($subTotal - $displayDiscount));

        // A5 SMART PAGINATION (Tuned for 140mm paper height)
        $singlePageCapacity = 8;

        if ($totalItemsCount <= $singlePageCapacity) {
            $pagesData = [
                [
                    'page_number' => 1,
                    'total_pages' => 1,
                    'is_first' => true,
                    'is_last' => true,
                    'items' => $items,
                    'show_full_header' => true,
                    'show_totals_and_signatures' => true,
                    'filler_count' => max(0, min(4, $singlePageCapacity - $totalItemsCount)),
                ]
            ];
        } else {
            $page1Cap = 12;
            $lastPageCap = 8;
            $middleCap = 15;

            $pagesData = [];
            $slicedPages = [];

            if ($totalItemsCount <= ($page1Cap + $lastPageCap)) {
                $lastCount = min($lastPageCap, max(3, (int) ceil($totalItemsCount / 2)));
                $p1Count = $totalItemsCount - $lastCount;
                $slicedPages[] = $items->slice(0, $p1Count);
                $slicedPages[] = $items->slice($p1Count);
            } else {
                $slicedPages[] = $items->slice(0, $page1Cap);
                $curr = $page1Cap;
                while (($totalItemsCount - $curr) > $lastPageCap) {
                    $take = min($middleCap, ($totalItemsCount - $curr) - $lastPageCap);
                    $slicedPages[] = $items->slice($curr, $take);
                    $curr += $take;
                }
                if ($curr < $totalItemsCount) {
                    $slicedPages[] = $items->slice($curr);
                }
            }

            $totalPages = count($slicedPages);
            foreach ($slicedPages as $idx => $pItems) {
                $pNum = $idx + 1;
                $isFirst = ($pNum === 1);
                $isLast = ($pNum === $totalPages);

                $pagesData[] = [
                    'page_number' => $pNum,
                    'total_pages' => $totalPages,
                    'is_first' => $isFirst,
                    'is_last' => $isLast,
                    'items' => $pItems,
                    'show_full_header' => $isFirst,
                    'show_totals_and_signatures' => $isLast,
                    'filler_count' => 0,
                ];
            }
        }
        $totalPagesCount = count($pagesData);
        $a4Sheets = array_chunk($pagesData, 2);
        $totalA4SheetsCount = count($a4Sheets);

        $isStaff = auth()->check() && (auth()->user()->role === 'staff' || (method_exists(auth()->user(), 'isStaff') && auth()->user()->isStaff()));
        $downloadRouteName = $isStaff ? 'staff.download.quotation' : 'admin.download.quotation';
    @endphp

    <style id="dynamic-print-style">
        @if($paper === 'a4')
        @page {
            size: A4 portrait;
            margin: 8mm;
        }
        @elseif($paper === 'a5-on-a4')
        @page {
            size: A4 portrait;
            margin: 0;
        }
        @elseif($paper === '80mm')
        @page {
            size: 80mm auto;
            margin: 2mm;
        }
        @else
        @page {
            size: 210mm 140mm;
            margin: 3mm 4mm;
        }
        @endif
    </style>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, Arial, Helvetica, sans-serif;
            font-size: 8.5pt;
            color: #111827;
            background: #e2e8f0;
            padding-top: 70px;
            padding-bottom: 30px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ── Top Floating Action Toolbar (No Print) ── */
        .print-toolbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: #16285A;
            color: white;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.25);
            z-index: 9999;
        }

        .toolbar-title {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .toolbar-title h3 {
            font-size: 15px;
            font-weight: 700;
            margin: 0;
            color: #ffffff;
        }

        .badge-info {
            background: rgba(255,255,255,0.2);
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-validity {
            background: #F59E0B;
            color: #111827;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .toolbar-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-toolbar {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .btn-print-main {
            background: #10B981;
            color: white;
        }
        .btn-print-main:hover {
            background: #059669;
        }

        .btn-format {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
        }
        .btn-format.active {
            background: #CC0E11;
            border-color: #CC0E11;
            box-shadow: 0 0 10px rgba(204,14,17,0.5);
        }
        .btn-format:hover:not(.active) {
            background: rgba(255,255,255,0.25);
        }

        .btn-download {
            background: #2563EB;
            color: white;
        }
        .btn-download:hover {
            background: #1D4ED8;
        }

        .btn-close-tb {
            background: #64748B;
            color: white;
        }
        .btn-close-tb:hover {
            background: #475569;
        }

        /* ── Canvas & Page Container ── */
        .print-canvas {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            margin: 0 auto;
        }

        /* ── Notice & Driver Warning Banner (Screen Only) ── */
        .a5-notice-banner {
            width: 210mm;
            background: #FFFBEB;
            border: 1.5px solid #F59E0B;
            border-radius: 8px;
            padding: 10px 14px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            box-shadow: 0 2px 10px rgba(245, 158, 11, 0.15);
            box-sizing: border-box;
            transition: all 0.2s ease;
        }

        .a5-notice-banner.notice-info {
            background: #EFF6FF;
            border-color: #3B82F6;
            box-shadow: 0 2px 10px rgba(59, 130, 246, 0.15);
        }

        .a5-notice-banner .notice-icon {
            font-size: 20px;
            line-height: 1;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .a5-notice-banner .notice-body {
            flex: 1;
            font-size: 8pt;
            line-height: 1.45;
            color: #92400E;
        }

        .a5-notice-banner.notice-info .notice-body {
            color: #1E40AF;
        }

        .a5-notice-banner .notice-title {
            font-weight: 700;
            font-size: 8.5pt;
            margin-bottom: 2px;
        }

        .a5-notice-banner .notice-desc {
            font-weight: 500;
        }

        .a5-notice-banner .notice-close {
            background: transparent;
            border: none;
            color: #94A3B8;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            padding: 0 4px;
            line-height: 1;
        }
        .a5-notice-banner .notice-close:hover {
            color: #334155;
        }

        /* ── MODE 1: A5 Landscape Mode (Screen Preview: Exact 210mm × 140mm) ── */
        .paper-mode-a5 .a5-a4-sheet {
            display: contents;
        }

        .paper-mode-a5 .a5-page {
            width: 210mm;
            height: 140mm;
            max-height: 140mm;
            background: #ffffff;
            border-radius: 4px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            padding: 3mm 4mm;
            position: relative;
            box-sizing: border-box;
            break-after: page;
            page-break-after: always;
            break-inside: avoid;
            page-break-inside: avoid;
            overflow: hidden;
        }
        .paper-mode-a5 .a5-page:last-child {
            break-after: avoid;
            page-break-after: avoid;
        }

        .paper-mode-a5 .a5-a4-cut-guide,
        .paper-mode-a5 .a5-a4-blank-area {
            display: none !important;
        }

        /* ── MODE 3: A5 on A4 Sheet Mode ── */
        .paper-mode-a5-on-a4 .a5-a4-sheet {
            width: 210mm;
            height: 280mm;
            min-height: 280mm;
            background: #ffffff;
            border-radius: 4px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            position: relative;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            break-after: page;
            page-break-after: always;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        .paper-mode-a5-on-a4 .a5-a4-sheet:last-child {
            break-after: avoid;
            page-break-after: avoid;
        }

        .paper-mode-a5-on-a4 .a5-page {
            width: 210mm;
            height: 140mm;
            min-height: 140mm;
            max-height: 140mm;
            background: #ffffff;
            padding: 3mm 4mm;
            position: relative;
            box-sizing: border-box;
            overflow: hidden;
            border-radius: 0;
            box-shadow: none;
        }

        .paper-mode-a5-on-a4 .a5-a4-cut-guide {
            width: 100%;
            height: 18px;
            border-top: 1px dashed #94A3B8;
            background: #F8FAFC;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7pt;
            color: #64748B;
            font-weight: 600;
            user-select: none;
            letter-spacing: 0.2px;
            box-sizing: border-box;
        }

        .paper-mode-a5-on-a4 .a5-a4-blank-area {
            flex: 1;
            background: repeating-linear-gradient(
                -45deg,
                #ffffff,
                #ffffff 15px,
                #F8FAFC 15px,
                #F8FAFC 30px
            );
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94A3B8;
            font-size: 8pt;
            font-style: italic;
            user-select: none;
        }

        /* ── MODE 2: A4 Portrait Mode ── */
        .paper-mode-a4 .a4-full-page {
            width: 210mm;
            min-height: 280mm;
            background: #ffffff;
            border-radius: 4px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            padding: 8mm;
            position: relative;
            box-sizing: border-box;
            break-after: avoid;
            page-break-after: avoid;
            break-inside: avoid;
            page-break-inside: avoid;
        }

        .paper-mode-a4 .a5-a4-cut-guide,
        .paper-mode-a4 .a5-a4-blank-area {
            display: none !important;
        }

        /* ── MODE 4: 80mm Thermal Receipt Mode ── */
        .paper-mode-80mm .thermal-render-container {
            width: 80mm;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .paper-mode-80mm .thermal-page {
            width: 80mm;
            background: #ffffff;
            padding: 5mm 4mm;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            font-family: 'Courier New', Courier, monospace, sans-serif;
            font-size: 8.5pt;
            box-sizing: border-box;
            color: #000000;
            border-radius: 4px;
        }
        .paper-mode-80mm .thermal-header {
            text-align: center;
            margin-bottom: 6px;
            border-bottom: 1.5px dashed #000;
            padding-bottom: 6px;
        }
        .paper-mode-80mm .thermal-header h2 {
            font-size: 11pt;
            font-weight: 800;
            margin: 0 0 2px 0;
            text-transform: uppercase;
        }
        .paper-mode-80mm .thermal-header p {
            font-size: 7.5pt;
            margin: 1px 0;
        }
        .paper-mode-80mm .thermal-table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
            font-size: 8pt;
        }
        .paper-mode-80mm .thermal-table th {
            border-bottom: 1px solid #000;
            text-align: left;
            padding: 3px 0;
            font-size: 7.5pt;
        }
        .paper-mode-80mm .thermal-table td {
            padding: 2.5px 0;
            vertical-align: top;
        }
        .paper-mode-80mm .thermal-totals {
            border-top: 1.5px dashed #000;
            padding-top: 4px;
            margin-top: 6px;
            font-size: 8.5pt;
        }
        .paper-mode-80mm .thermal-totals-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        .paper-mode-80mm .thermal-footer {
            text-align: center;
            margin-top: 8px;
            border-top: 1.5px dashed #000;
            padding-top: 6px;
            font-size: 7.5pt;
        }

        /* Border Wrapper */
        .inv-border-wrap {
            width: 100%;
            height: 100%;
            border: 1.5px solid #16285A;
            border-radius: 5px;
            background: #ffffff;
            padding: 5px 7px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .paper-mode-a4 .inv-border-wrap {
            height: auto;
            min-height: calc(280mm - 16mm);
            padding: 8px 12px;
        }

        /* Top Header */
        .tbl-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .tbl-header td {
            vertical-align: top;
        }

        .company-col {
            width: 58%;
            padding-right: 8px;
        }

        .details-col {
            width: 42%;
        }

        .company-title {
            font-size: 14pt;
            font-weight: 800;
            color: #16285A;
            letter-spacing: -0.2px;
            text-transform: uppercase;
            margin-bottom: 1px;
        }

        .paper-mode-a4 .company-title {
            font-size: 16pt;
        }

        .company-tagline {
            font-size: 7pt;
            font-weight: 700;
            color: #CC0E11;
            margin-bottom: 2px;
            font-style: italic;
        }

        .paper-mode-a4 .company-tagline {
            font-size: 8pt;
        }

        .company-address {
            font-size: 7pt;
            color: #374151;
            line-height: 1.25;
        }

        .paper-mode-a4 .company-address {
            font-size: 8pt;
        }

        /* Quotation Document Stamp */
        .quotation-badge-pill {
            display: inline-block;
            background: #16285A;
            color: #ffffff;
            font-size: 7.5pt;
            font-weight: 800;
            padding: 1px 7px;
            border-radius: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 3px;
        }

        /* Customer & Details Box */
        .details-box {
            border: 1.2px solid #16285A;
            border-radius: 4px;
            padding: 3px 5px;
            background: #ffffff;
        }

        .paper-mode-a4 .details-box {
            padding: 5px 8px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
        }

        .paper-mode-a4 .details-table {
            font-size: 8.5pt;
        }

        .details-table td {
            padding: 1px 2px;
            vertical-align: top;
        }

        .details-table td.lbl {
            width: 75px;
            font-weight: 700;
            color: #16285A;
            white-space: nowrap;
        }

        .details-table td.val {
            font-weight: 600;
            color: #111827;
        }

        /* Continuation Header (A5 Page 2 onwards) */
        .continuation-header-box {
            border: 1.2px solid #16285A;
            border-radius: 4px;
            padding: 4px 8px;
            background: #F8FAFC;
            margin-bottom: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 8pt;
        }

        .continuation-header-box .c-item {
            color: #16285A;
            font-weight: 700;
        }
        .continuation-header-box .c-item span {
            color: #111827;
            font-weight: 600;
        }

        /* Items Table */
        .items-box {
            border: 1.2px solid #16285A;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 4px;
            flex: 1;
        }

        .paper-mode-a4 .items-box {
            margin-bottom: 8px;
            min-height: 120mm;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
        }

        .paper-mode-a4 .items-table {
            font-size: 8.5pt;
        }

        .items-table th {
            background-color: #CC0E11;
            color: #ffffff;
            font-weight: 700;
            font-size: 7pt;
            text-transform: uppercase;
            padding: 2.5px 4px;
            text-align: left;
            border: none;
        }

        .paper-mode-a4 .items-table th {
            font-size: 8pt;
            padding: 4px 6px;
        }

        .items-table td {
            padding: 2px 4px;
            border-bottom: 0.5px solid #E5E7EB;
            color: #1F2937;
            vertical-align: middle;
            font-size: 7.5pt;
        }

        .paper-mode-a4 .items-table td {
            font-size: 8.5pt;
            padding: 3.5px 6px;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .items-table .num {
            text-align: right;
            white-space: nowrap;
        }

        .items-table .center {
            text-align: center;
        }

        .blank-row td {
            height: 12px;
            padding: 0;
            border-bottom: none;
        }

        /* Bottom Grid: Terms & Totals */
        .bottom-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }

        .paper-mode-a4 .bottom-table {
            margin-top: 6px;
        }

        .bottom-table td {
            vertical-align: top;
        }

        .terms-col {
            width: 58%;
            padding-right: 8px;
            font-size: 7pt;
            color: #4B5563;
            line-height: 1.25;
        }

        .paper-mode-a4 .terms-col {
            font-size: 8pt;
            line-height: 1.4;
        }

        .sinhala-note {
            font-size: 7.5pt;
            font-weight: 700;
            color: #CC0E11;
            margin-bottom: 2px;
        }

        .paper-mode-a4 .sinhala-note {
            font-size: 9pt;
        }

        .english-note {
            font-size: 6.5pt;
            color: #4B5563;
            margin-bottom: 3px;
        }

        .paper-mode-a4 .english-note {
            font-size: 7.5pt;
        }

        .validity-box-highlight {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: #FEF3C7;
            border: 1px solid #F59E0B;
            color: #92400E;
            padding: 2px 7px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 7pt;
            margin-top: 2px;
        }

        .paper-mode-a4 .validity-box-highlight {
            font-size: 8pt;
            padding: 3px 9px;
        }

        .totals-col {
            width: 42%;
        }

        /* Totals Box */
        .totals-box {
            border: 1.2px solid #16285A;
            border-radius: 4px;
            padding: 2px 4px;
            background: #ffffff;
        }

        .paper-mode-a4 .totals-box {
            padding: 4px 6px;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
        }

        .paper-mode-a4 .totals-table {
            font-size: 8.5pt;
        }

        .totals-table td {
            padding: 1.5px 2px;
        }

        .paper-mode-a4 .totals-table td {
            padding: 2.5px 4px;
        }

        .totals-table td.lbl {
            font-weight: 700;
            color: #16285A;
            width: 50%;
        }

        .totals-table td.val {
            text-align: right;
            font-weight: 700;
            color: #111827;
            width: 50%;
        }

        .totals-table tr.highlight td.val {
            color: #CC0E11;
            font-size: 8.5pt;
        }

        .paper-mode-a4 .totals-table tr.highlight td.val {
            font-size: 10pt;
        }

        /* Signatures */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
        }

        .paper-mode-a4 .signatures-table {
            margin-top: 10px;
        }

        .signatures-table td {
            text-align: center;
            vertical-align: bottom;
            font-size: 6.5pt;
            padding: 0 4px;
        }

        .paper-mode-a4 .signatures-table td {
            font-size: 8pt;
        }

        .sig-line {
            border-top: 1px dashed #9CA3AF;
            width: 90%;
            margin: 0 auto 2px auto;
        }

        .page-badge {
            font-size: 6.5pt;
            color: #6B7280;
            font-weight: 600;
            text-align: right;
            margin-top: 1px;
        }

        .continuation-badge {
            text-align: center;
            padding: 3px;
            font-size: 7.5pt;
            font-style: italic;
            color: #16285A;
            font-weight: 700;
        }

        /* ── Print Media Styles ── */
        @media print {
            body {
                background: transparent !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .print-toolbar,
            .a5-notice-banner {
                display: none !important;
            }

            .print-canvas {
                display: block !important;
                margin: 0 !important;
                gap: 0 !important;
                padding: 0 !important;
            }

            /* MODE 1: A5 Landscape Print */
            .paper-mode-a5 .a5-a4-sheet {
                display: contents !important;
            }

            .paper-mode-a5 .a5-page {
                border-radius: 0 !important;
                box-shadow: none !important;
                width: 100% !important;
                height: calc(140mm - 6mm) !important;
                max-height: calc(140mm - 6mm) !important;
                margin: 0 !important;
                padding: 0 !important;
                break-after: page !important;
                page-break-after: always !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
            }
            .paper-mode-a5 .a5-page:last-child {
                break-after: avoid !important;
                page-break-after: avoid !important;
            }

            /* MODE 3: A5 on A4 Sheet Print */
            .paper-mode-a5-on-a4 .a5-a4-sheet {
                width: 210mm !important;
                height: 280mm !important;
                max-height: 280mm !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
                background: transparent !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: flex-start !important;
                break-after: page !important;
                page-break-after: always !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
            }
            .paper-mode-a5-on-a4 .a5-a4-sheet:last-child {
                break-after: avoid !important;
                page-break-after: avoid !important;
            }

            .paper-mode-a5-on-a4 .a5-page {
                width: 210mm !important;
                height: 140mm !important;
                max-height: 140mm !important;
                padding: 3mm 4mm !important;
                box-sizing: border-box !important;
                margin: 0 !important;
                border: none !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                overflow: hidden !important;
                break-after: avoid !important;
                page-break-after: avoid !important;
            }

            .paper-mode-a5-on-a4 .a5-a4-cut-guide {
                width: 100% !important;
                height: 0 !important;
                border-top: 0.5px dashed #CBD5E1 !important;
                background: transparent !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: hidden !important;
            }
            .paper-mode-a5-on-a4 .a5-a4-cut-guide span {
                display: none !important;
            }

            .paper-mode-a5-on-a4 .a5-a4-blank-area {
                display: none !important;
            }

            /* MODE 4: 80mm Thermal Receipt Print */
            .paper-mode-80mm .thermal-page {
                width: 100% !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            /* MODE 2: A4 Portrait Print */
            .paper-mode-a4 .a4-full-page {
                border-radius: 0 !important;
                box-shadow: none !important;
                width: 100% !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                break-after: avoid !important;
                page-break-after: avoid !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
            }
        }
    </style>
</head>
<body class="{{ $paper === 'a4' ? 'paper-mode-a4' : ($paper === 'a5-on-a4' ? 'paper-mode-a5-on-a4' : ($paper === '80mm' ? 'paper-mode-80mm' : 'paper-mode-a5')) }}">

    <!-- ── FLOATING TOOLBAR ── -->
    <div class="print-toolbar">
        <div class="toolbar-title">
            <h3>🖨️ Quotation Print - {{ $quotation->quotation_number }}</h3>
            <span class="badge-validity">
                ⏳ Valid for {{ $validityDays }} Days (Until {{ $validUntil->format('d/m/Y') }})
            </span>
            <span class="badge-info" id="pages-badge">
                @if($paper === 'a4')
                    📑 1 Complete Single Page (A4 Portrait)
                @elseif($paper === '80mm')
                    🧾 80mm Thermal POS Receipt
                @elseif($paper === 'a5-on-a4')
                    📄 {{ $totalPagesCount }} A5 {{ $totalPagesCount > 1 ? 'Pages' : 'Page' }} ({{ $totalA4SheetsCount }} A4 {{ $totalA4SheetsCount > 1 ? 'Sheets' : 'Sheet' }})
                @else
                    📄 {{ $totalPagesCount }} A5 {{ $totalPagesCount > 1 ? 'Pages' : 'Page' }} (210×140mm)
                @endif
            </span>
        </div>

        <div class="toolbar-controls">
            <!-- Paper Format Switcher -->
            <button type="button" class="btn-toolbar btn-format {{ $paper === 'a5' ? 'active' : '' }}" id="btn-set-a5" onclick="setPaperFormat('a5')">
                📄 A5 (210×140mm)
            </button>
            <button type="button" class="btn-toolbar btn-format {{ $paper === 'a5-on-a4' ? 'active' : '' }}" id="btn-set-a5-on-a4" onclick="setPaperFormat('a5-on-a4')">
                📄 A5 on A4
            </button>
            <button type="button" class="btn-toolbar btn-format {{ $paper === 'a4' ? 'active' : '' }}" id="btn-set-a4" onclick="setPaperFormat('a4')">
                📑 A4 Portrait
            </button>
            <button type="button" class="btn-toolbar btn-format {{ $paper === '80mm' ? 'active' : '' }}" id="btn-set-80mm" onclick="setPaperFormat('80mm')">
                🧾 80mm Thermal
            </button>

            <!-- Download PDF Options -->
            <a href="{{ route($downloadRouteName, ['id' => $quotation->id, 'paper' => 'a5']) }}" class="btn-toolbar btn-download" id="link-download-a5" title="Download A5 PDF">
                📥 PDF (A5)
            </a>
            <a href="{{ route($downloadRouteName, ['id' => $quotation->id, 'paper' => 'a4']) }}" class="btn-toolbar btn-download" id="link-download-a4" title="Download A4 PDF">
                📥 PDF (A4)
            </a>

            <!-- Print Main -->
            <button type="button" class="btn-toolbar btn-print-main" onclick="triggerPrint()">
                🖨️ Print Now
            </button>

            <!-- Close -->
            <button type="button" class="btn-toolbar btn-close-tb" onclick="window.close()">
                ✖ Close
            </button>
        </div>
    </div>

    <!-- ── PRINT CANVAS ── -->
    <div class="print-canvas" id="main-print-canvas">

        <!-- ── Validity & Help Banner (Screen Only) ── -->
        <div class="a5-notice-banner notice-info" id="a5-notice-banner" style="display: flex;">
            <div class="notice-icon" id="notice-icon">⏳</div>
            <div class="notice-body">
                <div class="notice-title" id="notice-title">
                    Official Quotation — Valid for {{ $validityDays }} Days (Until {{ $validUntil->format('d/m/Y') }})
                </div>
                <div class="notice-desc" id="notice-desc">
                    This quotation is strictly valid for <strong>{{ $validityDays }} days</strong> from the date of issue (until <strong>{{ $validUntil->format('d/m/Y') }}</strong>). All prices and discounts are subject to confirmation thereafter.
                </div>
            </div>
            <button type="button" class="notice-close" onclick="document.getElementById('a5-notice-banner').style.display='none'" title="Dismiss">✕</button>
        </div>

        <!-- MODE 1 & 3: A5 Landscape / A5 on A4 Container -->
        <div class="a5-render-container" id="a5-container" style="{{ $paper === 'a4' || $paper === '80mm' ? 'display:none;' : 'display:contents;' }}">
            @foreach($a4Sheets as $sIdx => $sheetPages)
            <div class="a5-a4-sheet">
                @foreach($sheetPages as $pIdxInSheet => $pageData)
                @if($pIdxInSheet > 0)
                <div class="a5-a4-cut-guide">
                    <span>✂ Cut Line (140mm)</span>
                </div>
                @endif
                <div class="a5-page">
                    <div class="inv-border-wrap">
                    @if($pageData['show_full_header'])
                    <!-- 1. Header Section (Only on Page 1) -->
                    <table class="tbl-header">
                        <tr>
                            <td class="company-col">
                                <div class="company-title">{{ config('shop.name', 'SEWANAGALA CERAMIC') }}</div>
                                <div class="company-tagline">{{ config('shop.tagline', 'Importers of Wall Tiles & Floor Tiles, Bathroom Sets, Bathroom Fittings, Glass Doors, Aluminium Doors, Borders & Sanitaryware') }}</div>
                                <div class="company-address">
                                    {{ config('shop.address', 'No 86, Delgahamuwa, Ibbagamuwa.') }}<br>
                                    <strong>Tel:</strong> {{ config('shop.phone', '0778186280 / 0778186280 / 0372259999') }} &nbsp;|&nbsp; <strong>WhatsApp:</strong> {{ config('shop.whatsapp', '0778186280') }}
                                </div>
                                <div class="quotation-badge-pill">
                                    QUOTATION
                                </div>
                            </td>
                            <td class="details-col">
                                <div class="details-box">
                                    <table class="details-table">
                                        <tr>
                                            <td class="lbl">Customer</td>
                                            <td class="val">: {{ $billName }}</td>
                                        </tr>
                                        <tr>
                                            <td class="lbl">Address</td>
                                            <td class="val">: {{ $billAddress !== '' ? $billAddress : 'None' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="lbl">Tel</td>
                                            <td class="val">: {{ $billPhone !== '' ? $billPhone : 'None' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="lbl">Quotation No</td>
                                            <td class="val">: <strong>{{ $quotation->quotation_number }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="lbl">Date</td>
                                            <td class="val">: {{ $quotationDate->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="lbl">Valid Until</td>
                                            <td class="val" style="color: #CC0E11;">: <strong>{{ $validUntil->format('d/m/Y') }}</strong> <span style="font-size: 6.5pt; font-weight: normal; color: #92400E;">({{ $validityDays }} Days)</span></td>
                                        </tr>
                                        <tr>
                                            <td class="lbl">Sales Rep</td>
                                            <td class="val">: {{ $repName }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>
                    @else
                    <!-- 1b. Continuation Header (Page 2 onwards) -->
                    <div class="continuation-header-box">
                        <div class="c-item">Quotation: <span>{{ $quotation->quotation_number }}</span></div>
                        <div class="c-item">Customer: <span>{{ $billName }}</span></div>
                        <div class="c-item">Date: <span>{{ $quotationDate->format('d/m/Y') }}</span></div>
                        <div class="c-item" style="color: #CC0E11;">Valid: <span>{{ $validUntil->format('d/m/Y') }} ({{ $validityDays }} Days)</span></div>
                        <div class="c-item" style="color: #16285A;">Page {{ $pageData['page_number'] }} of {{ $pageData['total_pages'] }}</div>
                    </div>
                    @endif

                    <!-- 2. Items Table -->
                    <div class="items-box">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th style="width: 25px;" class="center">NO</th>
                                    <th style="width: 42%;">DESCRIPTION</th>
                                    <th style="width: 14%;" class="center">SIZE/MODEL</th>
                                    <th style="width: 8%;" class="center">QTY</th>
                                    <th style="width: 12%;" class="num">RATE (RS.)</th>
                                    <th style="width: 10%;" class="num">DISC.</th>
                                    <th style="width: 14%;" class="num">AMOUNT (RS.)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pageData['items'] as $itemIndex => $item)
                                @php
                                    $itemSize = $item->product_model ?: '-';
                                    if (empty($item->product_id)) {
                                        $itemSize = 'Service';
                                    }
                                    $lineTotal = ($item->unit_price - $item->discount_per_unit) * $item->quantity;
                                    $overallIndex = $items->search(fn($it) => $it->id === $item->id);
                                    $displayNo = ($overallIndex !== false) ? ($overallIndex + 1) : ($itemIndex + 1);
                                @endphp
                                <tr>
                                    <td class="center">{{ sprintf('%02d', $displayNo) }}</td>
                                    <td>
                                        <strong>{{ $item->product_name }}</strong>
                                        @if($item->product_code && $item->product_code !== $item->product_name)
                                            <span style="font-size: 7pt; color: #6B7280;">({{ $item->product_code }})</span>
                                        @endif
                                    </td>
                                    <td class="center">{{ $itemSize }}</td>
                                    <td class="center"><strong>{{ $item->quantity }}</strong></td>
                                    <td class="num">{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="num">{{ $item->discount_per_unit > 0 ? number_format($item->discount_per_unit, 2) : '-' }}</td>
                                    <td class="num"><strong>{{ number_format($lineTotal, 2) }}</strong></td>
                                </tr>
                                @endforeach

                                @for($f = 0; $f < ($pageData['filler_count'] ?? 0); $f++)
                                <tr class="blank-row">
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>

                    @if(!$pageData['is_last'])
                    <div class="continuation-badge">
                        ⬇ Continued on Page {{ $pageData['page_number'] + 1 }} of {{ $pageData['total_pages'] }}...
                    </div>
                    <div class="page-badge">
                        Page {{ $pageData['page_number'] }} of {{ $pageData['total_pages'] }}
                    </div>
                    @else

                    <!-- 3. Bottom Grid: Terms & Totals (On Final Page) -->
                    <table class="bottom-table">
                        <tr>
                            <td class="terms-col">
                                <div class="validity-note-heading" style="color: #CC0E11; font-weight: 700; font-size: 8pt; margin-bottom: 2px;">QUOTATION VALIDITY: {{ $validityDays }} DAYS ONLY</div>
                                <div class="english-note">This quotation is strictly valid for {{ $validityDays }} days from date of issue. Prices & discounts are subject to confirmation after validity period.</div>
                                <div class="validity-box-highlight">
                                    Quotation Validity: {{ $validityDays }} Days (Valid Until: {{ $validUntil->format('d/m/Y') }})
                                </div>
                            </td>
                            <td class="totals-col">
                                <div class="totals-box">
                                    <table class="totals-table">
                                        <tr>
                                            <td class="lbl">Sub Total</td>
                                            <td class="val">Rs. {{ number_format($subTotal, 2) }}</td>
                                        </tr>
                                        @if($displayDiscount > 0)
                                        <tr>
                                            <td class="lbl">Discount</td>
                                            <td class="val" style="color: #CC0E11;">- Rs. {{ number_format($displayDiscount, 2) }}</td>
                                        </tr>
                                        @endif
                                        <tr class="highlight">
                                            <td class="lbl">Quotation Total</td>
                                            <td class="val">Rs. {{ number_format($netTotal, 2) }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>

                    <!-- 4. Signatures (On Final Page) -->
                    <table class="signatures-table">
                        <tr>
                            <td style="width: 28%;">
                                <div class="sig-line"></div>
                                <strong>Prepared By</strong>
                            </td>
                            <td style="width: 44%; font-style: italic; color: #16285A; font-weight: 700;">
                                Thank you for your inquiry! Valid for {{ $validityDays }} Days.
                            </td>
                            <td style="width: 28%;">
                                <div class="sig-line"></div>
                                <strong>Authorised Signature</strong>
                            </td>
                        </tr>
                    </table>

                    @if($pageData['total_pages'] > 1)
                    <div class="page-badge">
                        Page {{ $pageData['page_number'] }} of {{ $pageData['total_pages'] }}
                    </div>
                    @endif
                    @endif
                </div>
            </div>
            @endforeach

            @if(count($sheetPages) === 1)
            <div class="a5-a4-cut-guide">
                <span>✂ Cut Line (140mm) — True A5 Quotation Above / Blank Lower Half</span>
            </div>
            <div class="a5-a4-blank-area">
                <span>Lower A4 half (blank for clean cutting)</span>
            </div>
            @endif
            </div>
            @endforeach
        </div>

        <!-- MODE 2: A4 Portrait Container (ONE SINGLE COMPLETE PAGE) -->
        <div class="a4-render-container" id="a4-container" style="{{ $paper === 'a4' ? 'display:contents;' : 'display:none;' }}">
            <div class="a4-full-page">
                <div class="inv-border-wrap">
                    <!-- 1. Header Section -->
                    <table class="tbl-header">
                        <tr>
                            <td class="company-col">
                                <div class="company-title">{{ config('shop.name', 'SEWANAGALA CERAMIC') }}</div>
                                <div class="company-tagline">{{ config('shop.tagline', 'Importers of Wall Tiles & Floor Tiles, Bathroom Sets, Bathroom Fittings, Glass Doors, Aluminium Doors, Borders & Sanitaryware') }}</div>
                                <div class="company-address">
                                    {{ config('shop.address', 'No 86, Delgahamuwa, Ibbagamuwa.') }}<br>
                                    <strong>Tel:</strong> {{ config('shop.phone', '0778186280 / 0778186280 / 0372259999') }} &nbsp;|&nbsp; <strong>WhatsApp:</strong> {{ config('shop.whatsapp', '0778186280') }}
                                </div>
                                <div class="quotation-badge-pill" style="font-size: 9pt; padding: 2px 10px; margin-top: 5px;">
                                    QUOTATION
                                </div>
                            </td>
                            <td class="details-col">
                                <div class="details-box">
                                    <table class="details-table">
                                        <tr>
                                            <td class="lbl">Customer</td>
                                            <td class="val">: {{ $billName }}</td>
                                        </tr>
                                        <tr>
                                            <td class="lbl">Address</td>
                                            <td class="val">: {{ $billAddress !== '' ? $billAddress : 'None' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="lbl">Tel</td>
                                            <td class="val">: {{ $billPhone !== '' ? $billPhone : 'None' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="lbl">Quotation No</td>
                                            <td class="val">: <strong>{{ $quotation->quotation_number }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="lbl">Date</td>
                                            <td class="val">: {{ $quotationDate->format('d/m/Y') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="lbl">Valid Until</td>
                                            <td class="val" style="color: #CC0E11;">: <strong>{{ $validUntil->format('d/m/Y') }}</strong> <span style="font-size: 7.5pt; color: #92400E;">({{ $validityDays }} Days)</span></td>
                                        </tr>
                                        <tr>
                                            <td class="lbl">Sales Rep</td>
                                            <td class="val">: {{ $repName }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>

                    <!-- 2. ALL Items in ONE Unified Table -->
                    <div class="items-box">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th style="width: 30px;" class="center">NO</th>
                                    <th style="width: 44%;">DESCRIPTION</th>
                                    <th style="width: 14%;" class="center">SIZE/MODEL</th>
                                    <th style="width: 8%;" class="center">QTY</th>
                                    <th style="width: 12%;" class="num">RATE (RS.)</th>
                                    <th style="width: 10%;" class="num">DISC.</th>
                                    <th style="width: 14%;" class="num">AMOUNT (RS.)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $itemIndex => $item)
                                @php
                                    $itemSize = $item->product_model ?: '-';
                                    if (empty($item->product_id)) {
                                        $itemSize = 'Service';
                                    }
                                    $lineTotal = ($item->unit_price - $item->discount_per_unit) * $item->quantity;
                                @endphp
                                <tr>
                                    <td class="center">{{ sprintf('%02d', $itemIndex + 1) }}</td>
                                    <td>
                                        <strong>{{ $item->product_name }}</strong>
                                        @if($item->product_code && $item->product_code !== $item->product_name)
                                            <span style="font-size: 7.5pt; color: #6B7280;">({{ $item->product_code }})</span>
                                        @endif
                                    </td>
                                    <td class="center">{{ $itemSize }}</td>
                                    <td class="center"><strong>{{ $item->quantity }}</strong></td>
                                    <td class="num">{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="num">{{ $item->discount_per_unit > 0 ? number_format($item->discount_per_unit, 2) : '-' }}</td>
                                    <td class="num"><strong>{{ number_format($lineTotal, 2) }}</strong></td>
                                </tr>
                                @endforeach

                                @php
                                    $a4Filler = max(0, 10 - $totalItemsCount);
                                @endphp
                                @for($f = 0; $f < $a4Filler; $f++)
                                <tr class="blank-row">
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>

                    <!-- 3. Bottom Grid: Terms & Totals -->
                    <table class="bottom-table">
                        <tr>
                            <td class="terms-col">
                                <div class="validity-note-heading" style="color: #CC0E11; font-weight: 700; font-size: 8pt; margin-bottom: 2px;">QUOTATION VALIDITY: {{ $validityDays }} DAYS ONLY</div>
                                <div class="english-note">This quotation is strictly valid for {{ $validityDays }} days from date of issue. Prices & discounts are subject to confirmation after validity period.</div>
                                <div class="validity-box-highlight">
                                    Quotation Validity: {{ $validityDays }} Days (Valid Until: {{ $validUntil->format('d/m/Y') }})
                                </div>
                            </td>
                            <td class="totals-col">
                                <div class="totals-box">
                                    <table class="totals-table">
                                        <tr>
                                            <td class="lbl">Sub Total</td>
                                            <td class="val">Rs. {{ number_format($subTotal, 2) }}</td>
                                        </tr>
                                        @if($displayDiscount > 0)
                                        <tr>
                                            <td class="lbl">Discount</td>
                                            <td class="val" style="color: #CC0E11;">- Rs. {{ number_format($displayDiscount, 2) }}</td>
                                        </tr>
                                        @endif
                                        <tr class="highlight">
                                            <td class="lbl">Quotation Total</td>
                                            <td class="val">Rs. {{ number_format($netTotal, 2) }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>

                    <!-- 4. Signatures -->
                    <table class="signatures-table">
                        <tr>
                            <td style="width: 28%;">
                                <div class="sig-line"></div>
                                <strong>Prepared By</strong>
                            </td>
                            <td style="width: 44%; font-style: italic; color: #16285A; font-weight: 700; font-size: 9pt;">
                                Thank you for your inquiry! Valid for {{ $validityDays }} Days.
                            </td>
                            <td style="width: 28%;">
                                <div class="sig-line"></div>
                                <strong>Authorised Signature</strong>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- MODE 4: 80mm Thermal POS Receipt Container -->
        <div class="thermal-render-container" id="thermal-container" style="{{ $paper === '80mm' ? 'display:flex;' : 'display:none;' }}">
            <div class="thermal-page">
                <div class="thermal-header">
                    <h2>{{ config('shop.name', 'SEWANAGALA CERAMIC') }}</h2>
                    <p>{{ config('shop.tagline', 'Importers of Wall Tiles & Floor Tiles, Bathroom Sets, Bathroom Fittings, Glass Doors, Aluminium Doors, Borders & Sanitaryware') }}</p>
                    <p>{{ config('shop.address', 'No 86, Delgahamuwa, Ibbagamuwa.') }}</p>
                    <p>Tel: {{ config('shop.phone', '0778186280 / 0778186280 / 0372259999') }} | WA: {{ config('shop.whatsapp', '0778186280') }}</p>
                    <div style="font-weight: bold; background: #000; color: #fff; padding: 2px 0; margin-top: 4px; font-size: 9pt;">*** QUOTATION ***</div>
                </div>

                <div style="font-size: 8pt; margin-bottom: 4px; border-bottom: 1px dashed #000; padding-bottom: 4px;">
                    <div><strong>Quotation #:</strong> {{ $quotation->quotation_number }}</div>
                    <div><strong>Date:</strong> {{ $quotationDate->format('d/m/Y') }}</div>
                    <div><strong>Valid Until:</strong> {{ $validUntil->format('d/m/Y') }} ({{ $validityDays }} Days)</div>
                    <div><strong>Customer:</strong> {{ $billName }}</div>
                </div>

                <table class="thermal-table">
                    <thead>
                        <tr>
                            <th style="width: 45%;">Item</th>
                            <th style="width: 15%; text-align: center;">Qty</th>
                            <th style="width: 20%; text-align: right;">Rate</th>
                            <th style="width: 20%; text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        @php
                            $lTotal = ($item->unit_price - $item->discount_per_unit) * $item->quantity;
                        @endphp
                        <tr>
                            <td>{{ $item->product_name }}</td>
                            <td style="text-align: center;">{{ $item->quantity }}</td>
                            <td style="text-align: right;">{{ number_format($item->unit_price, 2) }}</td>
                            <td style="text-align: right;">{{ number_format($lTotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="thermal-totals">
                    <div class="thermal-totals-row"><span>Sub Total:</span><span>Rs. {{ number_format($subTotal, 2) }}</span></div>
                    @if($displayDiscount > 0)
                    <div class="thermal-totals-row"><span>Discount:</span><span>- Rs. {{ number_format($displayDiscount, 2) }}</span></div>
                    @endif
                    <div class="thermal-totals-row" style="font-weight: bold; border-top: 1px solid #000; padding-top: 2px; margin-top: 2px;">
                        <span>Quotation Total:</span><span>Rs. {{ number_format($netTotal, 2) }}</span>
                    </div>
                </div>

                <div class="thermal-footer">
                    <div style="font-weight: bold;">Valid within {{ $validityDays }} days only.</div>
                    <div style="margin-top: 4px;">Thank you for your inquiry!</div>
                </div>
            </div>
        </div>

    </div>

    <!-- ── DYNAMIC SWITCHER & PRINT SCRIPT ── -->
    <script>
        let currentFormat = '{{ $paper }}';

        function applyPaperStyle(format) {
            const styleTag = document.getElementById('dynamic-print-style');
            if (!styleTag) return;
            if (format === 'a4') {
                styleTag.innerHTML = '@page { size: A4 portrait; margin: 8mm; }';
            } else if (format === 'a5-on-a4') {
                styleTag.innerHTML = '@page { size: A4 portrait; margin: 0; }';
            } else if (format === '80mm') {
                styleTag.innerHTML = '@page { size: 80mm auto; margin: 2mm; }';
            } else {
                styleTag.innerHTML = '@page { size: 210mm 140mm; margin: 3mm 4mm; }';
            }
        }

        function setPaperFormat(format) {
            currentFormat = format;
            const a5Container = document.getElementById('a5-container');
            const a4Container = document.getElementById('a4-container');
            const thermalContainer = document.getElementById('thermal-container');
            const btnA5 = document.getElementById('btn-set-a5');
            const btnA5onA4 = document.getElementById('btn-set-a5-on-a4');
            const btnA4 = document.getElementById('btn-set-a4');
            const btn80mm = document.getElementById('btn-set-80mm');
            const badge = document.getElementById('pages-badge');
            const noticeBanner = document.getElementById('a5-notice-banner');

            if (btnA5) btnA5.classList.remove('active');
            if (btnA5onA4) btnA5onA4.classList.remove('active');
            if (btnA4) btnA4.classList.remove('active');
            if (btn80mm) btn80mm.classList.remove('active');

            applyPaperStyle(format);

            if (format === 'a4') {
                document.body.className = 'paper-mode-a4';
                if (a5Container) a5Container.style.display = 'none';
                if (a4Container) a4Container.style.display = 'contents';
                if (thermalContainer) thermalContainer.style.display = 'none';
                if (btnA4) btnA4.classList.add('active');
                if (badge) badge.innerText = '📑 1 Complete Single Page (A4 Portrait)';
            } else if (format === '80mm') {
                document.body.className = 'paper-mode-80mm';
                if (a5Container) a5Container.style.display = 'none';
                if (a4Container) a4Container.style.display = 'none';
                if (thermalContainer) thermalContainer.style.display = 'flex';
                if (btn80mm) btn80mm.classList.add('active');
                if (badge) badge.innerText = '🧾 80mm Thermal POS Receipt';
            } else if (format === 'a5-on-a4') {
                document.body.className = 'paper-mode-a5-on-a4';
                if (a5Container) a5Container.style.display = 'contents';
                if (a4Container) a4Container.style.display = 'none';
                if (thermalContainer) thermalContainer.style.display = 'none';
                if (btnA5onA4) btnA5onA4.classList.add('active');
                if (badge) badge.innerText = '📄 {{ $totalPagesCount }} A5 {{ $totalPagesCount > 1 ? "Pages" : "Page" }} ({{ $totalA4SheetsCount }} A4 {{ $totalA4SheetsCount > 1 ? "Sheets" : "Sheet" }})';
            } else {
                document.body.className = 'paper-mode-a5';
                if (a5Container) a5Container.style.display = 'contents';
                if (a4Container) a4Container.style.display = 'none';
                if (thermalContainer) thermalContainer.style.display = 'none';
                if (btnA5) btnA5.classList.add('active');
                if (badge) badge.innerText = '📄 {{ $totalPagesCount }} A5 {{ $totalPagesCount > 1 ? "Pages" : "Page" }} (210×140mm)';
            }
        }

        function triggerPrint() {
            applyPaperStyle(currentFormat);
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    window.print();
                });
            });
        }
    </script>
</body>
</html>
