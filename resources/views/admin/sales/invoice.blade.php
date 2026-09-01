<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice - {{ $sale->invoice_number }}</title>
    @php
        $paper = isset($paper) && in_array(strtolower($paper), ['a4', 'a5']) ? strtolower($paper) : 'a5';
        $isA4 = ($paper === 'a4');

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

        // Items
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

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "DejaVu Sans", "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 8px;
            color: #111827;
            background: #ffffff;
            margin: 0;
            padding: 0;
        }

        @if($isA4)
        @page {
            size: 210mm 297mm;
            margin: 6mm 8mm 6mm 8mm;
        }
        @else
        @page {
            size: 210mm 148.5mm;
            margin: 4mm 5mm 4mm 5mm;
        }
        @endif

        .page-break {
            page-break-after: always;
        }

        .main-invoice-table {
            width: 100%;
            border: 1.2px solid #16285A;
            border-radius: 4px;
            background: #ffffff;
            border-collapse: collapse;
        }

        .main-invoice-cell {
            padding: 4px 6px;
            vertical-align: top;
        }

        /* Top Header Table */
        .tbl-header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
            table-layout: fixed;
        }

        .tbl-header td {
            vertical-align: top;
        }

        .company-col {
            width: 55%;
            padding-right: 6px;
        }

        .details-col {
            width: 45%;
        }

        .company-title {
            font-size: 13px;
            font-weight: bold;
            color: #16285A;
            letter-spacing: -0.2px;
            text-transform: uppercase;
            margin-bottom: 1px;
        }

        .a4-mode .company-title {
            font-size: 16px;
        }

        .company-tagline {
            font-size: 6.5px;
            font-weight: bold;
            color: #CC0E11;
            margin-bottom: 2px;
            font-style: italic;
        }

        .a4-mode .company-tagline {
            font-size: 7.5px;
        }

        .company-address {
            font-size: 6.5px;
            color: #374151;
            line-height: 1.2;
        }

        .a4-mode .company-address {
            font-size: 7.5px;
        }

        /* Customer & Details Box */
        .details-box {
            border: 1px solid #16285A;
            border-radius: 3px;
            padding: 2px 4px;
            background: #ffffff;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
            table-layout: fixed;
        }

        .a4-mode .details-table {
            font-size: 8px;
        }

        .details-table td {
            padding: 1px 2px;
            vertical-align: top;
        }

        .details-table td.lbl {
            width: 50px;
            font-weight: bold;
            color: #16285A;
            white-space: nowrap;
        }

        .details-table td.val {
            font-weight: bold;
            color: #111827;
        }

        /* Continuation Header (A5 Page 2 onwards) */
        .continuation-header-box {
            border: 1px solid #16285A;
            border-radius: 3px;
            padding: 3px 5px;
            background: #F8FAFC;
            margin-bottom: 3px;
            width: 100%;
        }

        .continuation-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
            table-layout: fixed;
        }

        .continuation-table td {
            padding: 1px 2px;
            color: #16285A;
            font-weight: bold;
        }
        .continuation-table td span {
            color: #111827;
            font-weight: normal;
        }

        /* Items Table */
        .items-box {
            border: 1px solid #16285A;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 3px;
            width: 100%;
        }

        .a4-mode .items-box {
            margin-bottom: 6px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
            table-layout: fixed;
        }

        .a4-mode .items-table {
            font-size: 8px;
        }

        .items-table th {
            background-color: #CC0E11;
            color: #ffffff;
            font-weight: bold;
            font-size: 6.5px;
            text-transform: uppercase;
            padding: 2.5px 3px;
            text-align: left;
            border: none;
        }

        .a4-mode .items-table th {
            font-size: 7.5px;
            padding: 3px 4px;
        }

        .items-table td {
            padding: 2px 3px;
            border-bottom: 0.5px solid #E5E7EB;
            color: #1F2937;
            vertical-align: middle;
            font-size: 7px;
            word-wrap: break-word;
        }

        .a4-mode .items-table td {
            font-size: 8px;
            padding: 3px 4px;
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
            height: 10px;
            padding: 0;
            border-bottom: none;
        }

        /* Column Widths (Sum = 100%) */
        .col-no { width: 5%; }
        .col-desc { width: 39%; }
        .col-size { width: 13%; }
        .col-qty { width: 7%; }
        .col-rate { width: 12%; }
        .col-disc { width: 9%; }
        .col-amount { width: 15%; }

        /* Returns Box */
        .returns-box {
            border: 1px solid #CC0E11;
            border-radius: 3px;
            margin-bottom: 2px;
            overflow: hidden;
            width: 100%;
        }

        .returns-header {
            background: #FEE2E2;
            color: #CC0E11;
            font-weight: bold;
            font-size: 6.5px;
            padding: 1.5px 3px;
        }

        /* Bottom Grid: Terms & Totals */
        .bottom-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
            table-layout: fixed;
        }

        .bottom-table td {
            vertical-align: top;
        }

        .terms-col {
            width: 55%;
            padding-right: 6px;
            font-size: 6.5px;
            color: #4B5563;
            line-height: 1.2;
        }

        .a4-mode .terms-col {
            font-size: 7.5px;
            line-height: 1.35;
        }

        .terms-note {
            font-size: 6.5px;
            font-weight: bold;
            color: #16285A;
            margin-bottom: 1px;
        }

        .totals-col {
            width: 45%;
        }

        /* Totals Box */
        .totals-box {
            border: 1px solid #16285A;
            border-radius: 3px;
            padding: 2px 4px;
            background: #ffffff;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
            table-layout: fixed;
        }

        .a4-mode .totals-table {
            font-size: 8px;
        }

        .totals-table td {
            padding: 1px 2px;
        }

        .totals-table td.lbl {
            font-weight: bold;
            color: #16285A;
            width: 48%;
        }

        .totals-table td.val {
            text-align: right;
            font-weight: bold;
            color: #111827;
            width: 52%;
        }

        .totals-table tr.highlight td.val {
            color: #CC0E11;
            font-size: 7.5px;
        }

        .a4-mode .totals-table tr.highlight td.val {
            font-size: 9px;
        }

        /* Signatures */
        .signatures-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
            table-layout: fixed;
        }

        .a4-mode .signatures-table {
            margin-top: 8px;
        }

        .signatures-table td {
            text-align: center;
            vertical-align: bottom;
            font-size: 6px;
            padding: 0 3px;
        }

        .a4-mode .signatures-table td {
            font-size: 7.5px;
        }

        .sig-line {
            border-top: 1px dashed #9CA3AF;
            width: 90%;
            margin: 0 auto 2px auto;
        }

        .page-badge {
            font-size: 6px;
            color: #6B7280;
            font-weight: bold;
            text-align: right;
            margin-top: 1px;
        }

        .continuation-badge {
            text-align: center;
            padding: 2px;
            font-size: 6.5px;
            font-style: italic;
            color: #16285A;
            font-weight: bold;
        }
    </style>
</head>
<body class="{{ $isA4 ? 'a4-mode' : 'a5-mode' }}">

@if($isA4)
    <!-- A4 MODE: SINGLE COMPLETE BILL -->
    <table class="main-invoice-table">
        <tr>
            <td class="main-invoice-cell">
                <!-- 1. Full Company Header -->
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
                                <th class="col-no center">NO</th>
                                <th class="col-desc">DESCRIPTION</th>
                                <th class="col-size center">SIZE/MODEL</th>
                                <th class="col-qty center">QTY</th>
                                <th class="col-rate num">RATE (RS.)</th>
                                <th class="col-disc num">DISC.</th>
                                <th class="col-amount num">AMOUNT (RS.)</th>
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
                                        <span style="font-size: 6px; color: #6B7280;">({{ $item->product_code }})</span>
                                    @endif
                                </td>
                                <td class="center">{{ $itemSize ?: '-' }}</td>
                                <td class="center"><strong>{{ $item->quantity }}</strong></td>
                                <td class="num">{{ number_format($item->unit_price, 2) }}</td>
                                <td class="num">{{ $item->discount_per_unit > 0 ? number_format($item->discount_per_unit, 2) : '-' }}</td>
                                <td class="num"><strong>{{ number_format($lineTotal, 2) }}</strong></td>
                            </tr>
                            @endforeach
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
                                <td class="col-no center">{{ $rIdx + 1 }}</td>
                                <td class="col-desc">{{ optional($return->product)->name ?? 'Returned item' }}</td>
                                <td class="col-size center">-</td>
                                <td class="col-qty center">{{ $return->return_quantity }}</td>
                                <td class="col-rate num">Rs.{{ number_format($return->selling_price, 2) }}</td>
                                <td class="col-disc num">-</td>
                                <td class="col-amount num" style="color: #CC0E11;"><strong>-Rs.{{ number_format($return->total_amount, 2) }}</strong></td>
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
                            <div class="terms-note">භාණ්ඩ මාරු කරනු ලබන්නේ දින 7ක් ඇතුලත පමණි. (මුදල් ආපසු නොගෙවේ)</div>
                            <div style="font-size: 6.5px; color: #4B5563;">Goods return will be accepted within 7 days only. Cash will not be refunded.</div>
                            <div style="font-size: 7px; font-weight: bold; color: #16285A; margin-top: 3px;">
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
                        <td style="width: 44%; font-style: italic; color: #16285A; font-weight: bold;">
                            Thank you for your business! We look forward to seeing you again.
                        </td>
                        <td style="width: 28%;">
                            <div class="sig-line"></div>
                            <strong>Authorised Signature</strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
@else
    <!-- A5 MODE: PAGINATED INTO A5 SHEETS -->
    @foreach($pagesData as $pIndex => $pageData)
        <table class="main-invoice-table {{ !$pageData['is_last'] ? 'page-break' : '' }}">
            <tr>
                <td class="main-invoice-cell">
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
                        <table class="continuation-table">
                            <tr>
                                <td>Invoice: <span>{{ $sale->invoice_number }}</span></td>
                                <td>Customer: <span>{{ $billName }}</span></td>
                                <td>Date: <span>{{ $sale->created_at->format('d/m/Y H:i') }}</span></td>
                                <td>Payment: <span>{{ $paymentLabel }}</span></td>
                                <td style="text-align: right; color: #CC0E11;">Page {{ $pageData['page_number'] }} of {{ $pageData['total_pages'] }}</td>
                            </tr>
                        </table>
                    </div>
                    @endif

                    <!-- 2. Items Table -->
                    <div class="items-box">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th class="col-no center">NO</th>
                                    <th class="col-desc">DESCRIPTION</th>
                                    <th class="col-size center">SIZE/MODEL</th>
                                    <th class="col-qty center">QTY</th>
                                    <th class="col-rate num">RATE (RS.)</th>
                                    <th class="col-disc num">DISC.</th>
                                    <th class="col-amount num">AMOUNT (RS.)</th>
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
                                            <span style="font-size: 6px; color: #6B7280;">({{ $item->product_code }})</span>
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
                        &gt;&gt; Continued on Page {{ $pageData['page_number'] + 1 }} of {{ $pageData['total_pages'] }}...
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
                                    <td class="col-no center">{{ $rIdx + 1 }}</td>
                                    <td class="col-desc">{{ optional($return->product)->name ?? 'Returned item' }}</td>
                                    <td class="col-size center">-</td>
                                    <td class="col-qty center">{{ $return->return_quantity }}</td>
                                    <td class="col-rate num">Rs.{{ number_format($return->selling_price, 2) }}</td>
                                    <td class="col-disc num">-</td>
                                    <td class="col-amount num" style="color: #CC0E11;"><strong>-Rs.{{ number_format($return->total_amount, 2) }}</strong></td>
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
                                <div class="terms-note">භාණ්ඩ මාරු කරනු ලබන්නේ දින 7ක් ඇතුලත පමණි. (මුදල් ආපසු නොගෙවේ)</div>
                                <div style="font-size: 6px; color: #4B5563;">Goods return accepted within 7 days only. Cash will not be refunded.</div>
                                <div style="font-size: 6.5px; font-weight: bold; color: #16285A; margin-top: 2px;">
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
                            <td style="width: 44%; font-style: italic; color: #16285A; font-weight: bold;">
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
                </td>
            </tr>
        </table>
    @endforeach
@endif

</body>
</html>
