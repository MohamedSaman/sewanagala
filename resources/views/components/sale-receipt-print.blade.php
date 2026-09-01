<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sale Receipt - {{ $sale->invoice_number }}</title>
    @php
        $paper = isset($paper) && in_array(strtolower($paper), ['a4', 'a5']) ? strtolower($paper) : 'a5';
        $viewOnly = isset($viewOnly) ? (bool)$viewOnly : false;

        // Customer Details
        $billName = trim((string) (optional($sale->customer)->name ?? ''));
        $billAddress = trim((string) (optional($sale->customer)->address ?? ''));
        $billPhone = trim((string) (optional($sale->customer)->phone ?? ''));
        if ($billName === '' || is_numeric($billName)) {
            $billName = 'Walking Customer';
        }

        // Payment Mode Label
        $paymentMethodLabels = [
            'cash' => 'Cash',
            'cheque' => 'Cheque',
            'bank_transfer' => 'Bank Transfer',
            'credit_card' => 'Credit Card',
        ];
        $paymentMethods = $sale->payments->pluck('payment_method')->filter()->unique()->values();
        if ($paymentMethods->count() > 1) {
            $methodText = $paymentMethods->map(fn ($m) => $paymentMethodLabels[$m] ?? ucwords(str_replace('_', ' ', $m)))->implode(' + ');
            $paymentLabel = 'Multiple (' . $methodText . ')';
        } elseif ($paymentMethods->count() === 1) {
            $singleMethod = $paymentMethods->first();
            $paymentLabel = $paymentMethodLabels[$singleMethod] ?? ucwords(str_replace('_', ' ', $singleMethod));
        } else {
            $paymentLabel = ($sale->due_amount ?? 0) > 0 ? 'Due' : 'Cash';
        }

        // Calculations
        $displayDiscount = max(0, (float) ($sale->discount_amount ?? 0));
        $returnItems = $sale->returns ?? collect();
        $returnTotal = (float) $returnItems->sum('total_amount');
        $netTotal = max(0, (float) $sale->total_amount - $returnTotal);
        $displayPaid = min($sale->payments->sum('amount'), $netTotal);
        if ($displayPaid == 0 && ($sale->due_amount ?? 0) < $netTotal) {
            $displayPaid = max(0, $netTotal - (float) ($sale->due_amount ?? 0));
        }
        $displayBalance = max(0, $netTotal - $displayPaid);

        // Sales Rep Name
        $repName = '-';
        if ($sale->sales_rep_id) {
            $repUser = \App\Models\User::find($sale->sales_rep_id);
            $repName = $repUser ? $repUser->name : '-';
        } elseif ($sale->user_id) {
            $repUser = \App\Models\User::find($sale->user_id);
            $repName = $repUser ? $repUser->name : '-';
        }

        // All Items
        $items = $sale->items->values();
        $totalItemsCount = $items->count();
        $hasReturns = $returnItems->count() > 0;

        // A5 SMART PAGINATION
        $singlePageCapacity = $hasReturns ? 6 : 8;

        if ($totalItemsCount <= $singlePageCapacity) {
            $pagesData = [
                [
                    'page_number' => 1,
                    'total_pages' => 1,
                    'is_first' => true,
                    'is_last' => true,
                    'items' => $items,
                    'returns' => $returnItems,
                    'show_full_header' => true,
                    'show_totals_and_signatures' => true,
                    'filler_count' => max(0, min(4, $singlePageCapacity - $totalItemsCount)),
                ]
            ];
        } else {
            $page1Cap = 13;
            $lastPageCap = $hasReturns ? 6 : 8;
            $middleCap = 16;

            $pagesData = [];
            $slicedPages = [];

            if ($totalItemsCount <= ($page1Cap + $lastPageCap)) {
                $slicedPages[] = $items->slice(0, $page1Cap);
                $slicedPages[] = $items->slice($page1Cap);
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
                    'returns' => $isLast ? $returnItems : collect(),
                    'show_full_header' => $isFirst,
                    'show_totals_and_signatures' => $isLast,
                    'filler_count' => 0,
                ];
            }
        }
        $totalPagesCount = count($pagesData);
    @endphp

    <style id="dynamic-print-style">
        @if($paper === 'a4')
        @page {
            size: A4 portrait;
            margin: 8mm 8mm 8mm 8mm;
        }
        @else
        @page {
            size: A5 landscape;
            margin: 3mm 4mm 3mm 4mm;
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

        /* ── MODE 1: A5 Landscape Mode (Screen) ── */
        .paper-mode-a5 .a5-page {
            width: 204mm;
            height: 140mm;
            max-height: 140mm;
            background: #ffffff;
            border-radius: 4px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            padding: 4px;
            position: relative;
            box-sizing: border-box;
            page-break-after: always;
            page-break-inside: avoid;
            overflow: hidden;
        }
        .paper-mode-a5 .a5-page:last-child {
            page-break-after: avoid;
        }

        /* ── MODE 2: A4 Portrait Mode (Single Complete Bill on Screen) ── */
        .paper-mode-a4 .a4-full-page {
            width: 204mm;
            min-height: 280mm;
            background: #ffffff;
            border-radius: 4px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
            padding: 6px;
            position: relative;
            box-sizing: border-box;
            page-break-after: avoid;
            page-break-inside: avoid;
        }

        /* Border Wrapper */
        .inv-border-wrap {
            width: 100%;
            height: 100%;
            border: 1.5px solid #16285A;
            border-radius: 5px;
            background: #ffffff;
            padding: 6px 8px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .paper-mode-a4 .inv-border-wrap {
            height: auto;
            min-height: 275mm;
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

        /* Customer & Invoice Box */
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
            width: 60px;
            font-weight: 700;
            color: #16285A;
            white-space: nowrap;
        }

        .details-table td.val {
            font-weight: 600;
            color: #111827;
        }

        /* Continuation Header (A5 Page 2 onwards - NO company header) */
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

        /* Returns Box */
        .returns-box {
            border: 1px solid #CC0E11;
            border-radius: 4px;
            margin-bottom: 4px;
            overflow: hidden;
        }

        .returns-header {
            background: #FEE2E2;
            color: #CC0E11;
            font-weight: 700;
            font-size: 7pt;
            padding: 2px 5px;
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
            font-size: 7pt;
            font-weight: 700;
            color: #16285A;
            margin-bottom: 1px;
        }

        .paper-mode-a4 .sinhala-note {
            font-size: 8.5pt;
        }

        .english-note {
            font-size: 6.5pt;
            color: #4B5563;
            margin-bottom: 2px;
        }

        .paper-mode-a4 .english-note {
            font-size: 7.5pt;
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
            padding: 1px 2px;
        }

        .paper-mode-a4 .totals-table td {
            padding: 2px 4px;
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
            font-size: 8pt;
        }

        .paper-mode-a4 .totals-table tr.highlight td.val {
            font-size: 9.5pt;
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

            .print-toolbar {
                display: none !important;
            }

            .print-canvas {
                display: block !important;
                margin: 0 !important;
                gap: 0 !important;
            }

            .paper-mode-a5 .a5-page {
                border-radius: 0 !important;
                box-shadow: none !important;
                width: 100% !important;
                height: 100% !important;
                max-height: 140mm !important;
                margin: 0 !important;
                padding: 0 !important;
                page-break-after: always;
                page-break-inside: avoid !important;
                overflow: hidden !important;
            }
            .paper-mode-a5 .a5-page:last-child {
                page-break-after: avoid !important;
            }

            .paper-mode-a4 .a4-full-page {
                border-radius: 0 !important;
                box-shadow: none !important;
                width: 100% !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                page-break-after: avoid !important;
                page-break-inside: avoid !important;
            }
        }
    </style>
</head>
<body class="{{ $paper === 'a4' ? 'paper-mode-a4' : 'paper-mode-a5' }}">

    <!-- ── FLOATING TOOLBAR ── -->
    <div class="print-toolbar">
        <div class="toolbar-title">
            <h3>🖨️ Sale Invoice Print</h3>
            <span class="badge-info" id="pages-badge">
                @if($paper === 'a4')
                    📑 1 Complete Single Bill (A4 Format)
                @else
                    📄 {{ $totalPagesCount }} A5 {{ $totalPagesCount > 1 ? 'Pages' : 'Page' }}
                @endif
            </span>
        </div>

        <div class="toolbar-controls">
            <!-- Paper Format Switcher -->
            <button type="button" class="btn-toolbar btn-format {{ $paper === 'a5' ? 'active' : '' }}" id="btn-set-a5" onclick="setPaperFormat('a5')">
                📄 A5 Paper (Landscape)
            </button>
            <button type="button" class="btn-toolbar btn-format {{ $paper === 'a4' ? 'active' : '' }}" id="btn-set-a4" onclick="setPaperFormat('a4')">
                📑 A4 Paper (Single Full Bill)
            </button>

            <!-- Download PDF Options -->
            <a href="{{ route('admin.download.sale', ['id' => $sale->id, 'paper' => 'a5']) }}" class="btn-toolbar btn-download" id="link-download-a5" title="Download A5 PDF">
                📥 PDF (A5)
            </a>
            <a href="{{ route('admin.download.sale', ['id' => $sale->id, 'paper' => 'a4']) }}" class="btn-toolbar btn-download" id="link-download-a4" title="Download A4 PDF">
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

        <!-- MODE 1: A5 Landscape Container (Paginated into A5 pages) -->
        <div class="a5-render-container" id="a5-container" style="{{ $paper === 'a4' ? 'display:none;' : 'display:contents;' }}">
            @foreach($pagesData as $pIdx => $pageData)
            <div class="a5-page">
                <div class="inv-border-wrap">
                    @if($pageData['show_full_header'])
                    <!-- 1. Header Section (Only on Page 1) -->
                    <table class="tbl-header">
                        <tr>
                            <td class="company-col">
                                <div class="company-title">{{ config('shop.name', 'THIHARIYA TILE CENTER') }}</div>
                                <div class="company-tagline">{{ config('shop.tagline', 'IMPORTERS & DEALERS IN WALL TILES, FLOOR TILES & SANITARYWARE Etc...') }}</div>
                                <div class="company-address">
                                    {{ config('shop.address', 'N 122/1H, Kandy Road, Thihariya, Sri Lanka.') }}<br>
                                    <strong>Tel:</strong> {{ config('shop.phone', '+0332 290 295') }} &nbsp;|&nbsp; <strong>WhatsApp:</strong> {{ config('shop.whatsapp', '+94 77 085 6464') }}
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
                                            <td class="lbl">Invoice No</td>
                                            <td class="val">: <strong>{{ $sale->invoice_number }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="lbl">Date</td>
                                            <td class="val">: {{ $sale->created_at->format('d/m/Y H:i') }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>
                    @else
                    <!-- 1b. Continuation Header (Page 2 onwards - NO company header) -->
                    <div class="continuation-header-box">
                        <div class="c-item">Invoice: <span>{{ $sale->invoice_number }}</span></div>
                        <div class="c-item">Customer: <span>{{ $billName }}</span></div>
                        <div class="c-item">Date: <span>{{ $sale->created_at->format('d/m/Y H:i') }}</span></div>
                        <div class="c-item">Payment: <span>{{ $paymentLabel }}</span></div>
                        <div class="c-item" style="color: #CC0E11;">Page {{ $pageData['page_number'] }} of {{ $pageData['total_pages'] }}</div>
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
                                    $itemSize = $item->product_model ?? (optional($item->product)->model ?? '-');
                                    if (empty($item->product_id)) {
                                        $itemSize = 'Service';
                                    }
                                    $lineTotal = ($item->unit_price - $item->discount_per_unit) * $item->quantity;
                                    $overallIndex = $sale->items->search(fn($it) => $it->id === $item->id);
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
                                    <td class="center">{{ $itemSize ?: '-' }}</td>
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

                    <!-- 3. Returned Items if Any (On Final Page) -->
                    @if($returnItems->count() > 0)
                    <div class="returns-box">
                        <div class="returns-header">RETURNED ITEMS</div>
                        <table class="items-table">
                            <tbody>
                                @foreach($returnItems as $rIdx => $return)
                                <tr>
                                    <td style="width: 25px;" class="center">{{ $rIdx + 1 }}</td>
                                    <td>{{ optional($return->product)->name ?? 'Returned item' }}</td>
                                    <td style="width: 15%;" class="center">Qty: {{ $return->return_quantity }}</td>
                                    <td style="width: 20%;" class="num">Rate: Rs.{{ number_format($return->selling_price, 2) }}</td>
                                    <td style="width: 20%;" class="num"><strong>-Rs.{{ number_format($return->total_amount, 2) }}</strong></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    <!-- 4. Bottom Grid: Terms & Totals (On Final Page) -->
                    <table class="bottom-table">
                        <tr>
                            <td class="terms-col">
                                <div class="sinhala-note">ඔබ රැගෙන යන භාණ්ඩ දින 7ක් ඇතුලත මාරුකර දෙනු ලැබේ. මුදල් ආපසු ගෙවනු නොලැබේ.</div>
                                <div class="english-note">Goods return accepted within 7 days only. Cash will not be refunded.</div>
                                <div style="font-size: 7.5pt; font-weight: 700; color: #16285A;">
                                    Payment Mode: <span style="color: #111827;">{{ $paymentLabel }}</span>
                                </div>
                            </td>
                            <td class="totals-col">
                                <div class="totals-box">
                                    <table class="totals-table">
                                        <tr>
                                            <td class="lbl">Sub Total</td>
                                            <td class="val">Rs. {{ number_format($sale->total_amount + $displayDiscount, 2) }}</td>
                                        </tr>
                                        @if($displayDiscount > 0)
                                        <tr>
                                            <td class="lbl">Discount</td>
                                            <td class="val" style="color: #CC0E11;">- Rs. {{ number_format($displayDiscount, 2) }}</td>
                                        </tr>
                                        @endif
                                        @if($returnTotal > 0)
                                        <tr>
                                            <td class="lbl">Returns</td>
                                            <td class="val" style="color: #CC0E11;">- Rs. {{ number_format($returnTotal, 2) }}</td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td class="lbl">Net Total</td>
                                            <td class="val">Rs. {{ number_format($netTotal, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="lbl">Paid</td>
                                            <td class="val">Rs. {{ number_format($displayPaid, 2) }}</td>
                                        </tr>
                                        <tr class="highlight">
                                            <td class="lbl">Balance Due</td>
                                            <td class="val">Rs. {{ number_format($displayBalance, 2) }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>

                    <!-- 5. Signatures (On Final Page) -->
                    <table class="signatures-table">
                        <tr>
                            <td style="width: 28%;">
                                <div class="sig-line"></div>
                                <strong>Checked By</strong>
                            </td>
                            <td style="width: 44%; font-style: italic; color: #16285A; font-weight: 700;">
                                Thank you for your business!
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
        </div>

        <!-- MODE 2: A4 Portrait Container (ONE SINGLE COMPLETE BILL) -->
        <div class="a4-render-container" id="a4-container" style="{{ $paper === 'a4' ? 'display:contents;' : 'display:none;' }}">
            <div class="a4-full-page">
                <div class="inv-border-wrap">
                    <!-- 1. Header Section -->
                    <table class="tbl-header">
                        <tr>
                            <td class="company-col">
                                <div class="company-title">{{ config('shop.name', 'THIHARIYA TILE CENTER') }}</div>
                                <div class="company-tagline">{{ config('shop.tagline', 'IMPORTERS & DEALERS IN WALL TILES, FLOOR TILES & SANITARYWARE Etc...') }}</div>
                                <div class="company-address">
                                    {{ config('shop.address', 'N 122/1H, Kandy Road, Thihariya, Sri Lanka.') }}<br>
                                    <strong>Tel:</strong> {{ config('shop.phone', '+0332 290 295') }} &nbsp;|&nbsp; <strong>WhatsApp:</strong> {{ config('shop.whatsapp', '+94 77 085 6464') }}
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
                                            <td class="lbl">Invoice No</td>
                                            <td class="val">: <strong>{{ $sale->invoice_number }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td class="lbl">Date</td>
                                            <td class="val">: {{ $sale->created_at->format('d/m/Y H:i') }}</td>
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
                                    $itemSize = $item->product_model ?? (optional($item->product)->model ?? '-');
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
                                    <td class="center">{{ $itemSize ?: '-' }}</td>
                                    <td class="center"><strong>{{ $item->quantity }}</strong></td>
                                    <td class="num">{{ number_format($item->unit_price, 2) }}</td>
                                    <td class="num">{{ $item->discount_per_unit > 0 ? number_format($item->discount_per_unit, 2) : '-' }}</td>
                                    <td class="num"><strong>{{ number_format($lineTotal, 2) }}</strong></td>
                                </tr>
                                @endforeach

                                @php
                                    $a4Filler = max(0, 8 - $totalItemsCount);
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

                    <!-- 3. Returned Items if Any -->
                    @if($returnItems->count() > 0)
                    <div class="returns-box">
                        <div class="returns-header">RETURNED ITEMS</div>
                        <table class="items-table">
                            <tbody>
                                @foreach($returnItems as $rIdx => $return)
                                <tr>
                                    <td style="width: 30px;" class="center">{{ $rIdx + 1 }}</td>
                                    <td>{{ optional($return->product)->name ?? 'Returned item' }}</td>
                                    <td style="width: 15%;" class="center">Qty: {{ $return->return_quantity }}</td>
                                    <td style="width: 20%;" class="num">Rate: Rs.{{ number_format($return->selling_price, 2) }}</td>
                                    <td style="width: 20%;" class="num"><strong>-Rs.{{ number_format($return->total_amount, 2) }}</strong></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif

                    <!-- 4. Bottom Grid: Terms & Totals -->
                    <table class="bottom-table">
                        <tr>
                            <td class="terms-col">
                                <div class="sinhala-note">ඔබ රැගෙන යන භාණ්ඩ දින 7ක් ඇතුලත මාරුකර දෙනු ලැබේ. මුදල් ආපසු ගෙවනු නොලැබේ.</div>
                                <div class="english-note">Goods return will be accepted within 7 days only. Cash will not be refunded.</div>
                                <div style="font-size: 8.5pt; font-weight: 700; color: #16285A; margin-top: 4px;">
                                    Payment Mode: <span style="color: #111827;">{{ $paymentLabel }}</span>
                                </div>
                            </td>
                            <td class="totals-col">
                                <div class="totals-box">
                                    <table class="totals-table">
                                        <tr>
                                            <td class="lbl">Sub Total</td>
                                            <td class="val">Rs. {{ number_format($sale->total_amount + $displayDiscount, 2) }}</td>
                                        </tr>
                                        @if($displayDiscount > 0)
                                        <tr>
                                            <td class="lbl">Discount</td>
                                            <td class="val" style="color: #CC0E11;">- Rs. {{ number_format($displayDiscount, 2) }}</td>
                                        </tr>
                                        @endif
                                        @if($returnTotal > 0)
                                        <tr>
                                            <td class="lbl">Returns</td>
                                            <td class="val" style="color: #CC0E11;">- Rs. {{ number_format($returnTotal, 2) }}</td>
                                        </tr>
                                        @endif
                                        <tr>
                                            <td class="lbl">Net Total</td>
                                            <td class="val">Rs. {{ number_format($netTotal, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="lbl">Paid</td>
                                            <td class="val">Rs. {{ number_format($displayPaid, 2) }}</td>
                                        </tr>
                                        <tr class="highlight">
                                            <td class="lbl">Balance Due</td>
                                            <td class="val">Rs. {{ number_format($displayBalance, 2) }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>

                    <!-- 5. Signatures -->
                    <table class="signatures-table">
                        <tr>
                            <td style="width: 28%;">
                                <div class="sig-line"></div>
                                <strong>Checked By</strong>
                            </td>
                            <td style="width: 44%; font-style: italic; color: #16285A; font-weight: 700; font-size: 9pt;">
                                Thank you for your business! We look forward to seeing you again.
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

    </div>

    <!-- ── DYNAMIC SWITCHER & PRINT SCRIPT ── -->
    <script>
        let currentFormat = '{{ $paper }}';

        function setPaperFormat(format) {
            currentFormat = format;
            const styleTag = document.getElementById('dynamic-print-style');
            const a5Container = document.getElementById('a5-container');
            const a4Container = document.getElementById('a4-container');
            const btnA5 = document.getElementById('btn-set-a5');
            const btnA4 = document.getElementById('btn-set-a4');
            const badge = document.getElementById('pages-badge');

            if (format === 'a4') {
                document.body.className = 'paper-mode-a4';
                a5Container.style.display = 'none';
                a4Container.style.display = 'contents';
                btnA4.classList.add('active');
                btnA5.classList.remove('active');
                badge.innerText = '📑 1 Complete Single Bill (A4 Format)';
                styleTag.innerHTML = '@page { size: A4 portrait; margin: 8mm; }';
            } else {
                document.body.className = 'paper-mode-a5';
                a5Container.style.display = 'contents';
                a4Container.style.display = 'none';
                btnA5.classList.add('active');
                btnA4.classList.remove('active');
                badge.innerText = '📄 {{ $totalPagesCount }} A5 {{ $totalPagesCount > 1 ? "Pages" : "Page" }}';
                styleTag.innerHTML = '@page { size: A5 landscape; margin: 3mm 4mm; }';
            }
        }

        function triggerPrint() {
            window.print();
        }
    </script>
</body>
</html>
