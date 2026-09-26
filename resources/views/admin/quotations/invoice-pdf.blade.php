<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Quotation - {{ $quotation->quotation_number }}</title>
    @php
        $paper = isset($paper) && in_array(strtolower($paper), ['a4', 'a5']) ? strtolower($paper) : 'a5';
        $isA4 = ($paper === 'a4');

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

        // A5 SMART PAGINATION
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
            $page1Cap = 14;
            $lastPageCap = 8;
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
            size: 210mm 280mm;
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
            padding: 4px 6px 2px 6px;
            vertical-align: top;
        }

        .main-invoice-footer-cell {
            padding: 2px 6px 4px 6px;
            vertical-align: bottom;
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

        .quotation-badge {
            display: inline-block;
            background: #16285A;
            color: #ffffff;
            font-size: 7px;
            font-weight: bold;
            padding: 1px 6px;
            border-radius: 2px;
            margin-top: 3px;
            text-transform: uppercase;
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
            width: 65px;
            font-weight: bold;
            color: #16285A;
            white-space: nowrap;
        }

        .details-table td.val {
            font-weight: bold;
            color: #111827;
        }

        /* Continuation Header */
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
        .col-desc { width: 41%; }
        .col-size { width: 14%; }
        .col-qty { width: 7%; }
        .col-rate { width: 12%; }
        .col-disc { width: 8%; }
        .col-amount { width: 13%; }

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
            font-size: 7px;
            font-weight: bold;
            color: #CC0E11;
            margin-bottom: 1px;
        }

        .a4-mode .terms-note {
            font-size: 8px;
        }

        .validity-tag {
            display: inline-block;
            background: #FEF3C7;
            border: 0.5px solid #F59E0B;
            color: #92400E;
            padding: 1.5px 5px;
            font-weight: bold;
            font-size: 6.5px;
            margin-top: 2px;
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
            padding: 1.5px 2px;
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
            font-size: 8px;
        }

        .a4-mode .totals-table tr.highlight td.val {
            font-size: 9.5px;
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
                            <div class="company-title">{{ config('shop.name', 'SEWANAGALA CERAMIC') }}</div>
                            <div class="company-tagline">{{ config('shop.tagline', 'Importers of Wall Tiles & Floor Tiles, Bathroom Sets, Bathroom Fittings, Glass Doors, Aluminium Doors, Borders & Sanitaryware') }}</div>
                            <div class="company-address">
                                {{ config('shop.address', 'No 86, Delgahamuwa, Ibbagamuwa.') }}<br>
                                <strong>Tel:</strong> {{ config('shop.phone', '0778186280 / 0778186280 / 0372259999') }} &nbsp;|&nbsp; <strong>WhatsApp:</strong> {{ config('shop.whatsapp', '0778186280') }}
                            </div>
                            <div class="quotation-badge">OFFICIAL QUOTATION</div>
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
                                        <td class="val" style="color: #CC0E11;">: <strong>{{ $validUntil->format('d/m/Y') }}</strong> <span style="font-size: 6.5px; color: #92400E;">({{ $validityDays }} Days)</span></td>
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
                                        <span style="font-size: 6px; color: #6B7280;">({{ $item->product_code }})</span>
                                    @endif
                                </td>
                                <td class="center">{{ $itemSize }}</td>
                                <td class="center"><strong>{{ $item->quantity }}</strong></td>
                                <td class="num">{{ number_format($item->unit_price, 2) }}</td>
                                <td class="num">{{ $item->discount_per_unit > 0 ? number_format($item->discount_per_unit, 2) : '-' }}</td>
                                <td class="num"><strong>{{ number_format($lineTotal, 2) }}</strong></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </td>
        </tr>
        <tr>
            <td class="main-invoice-footer-cell">
                <!-- 3. Bottom Grid: Terms & Totals -->
                <table class="bottom-table">
                    <tr>
                        <td class="terms-col">
                            <div class="terms-note">QUOTATION VALIDITY: {{ $validityDays }} DAYS ONLY</div>
                            <div style="font-size: 6.5px; color: #4B5563;">This quotation is strictly valid for {{ $validityDays }} days from date of issue. Prices & discounts are subject to confirmation after validity period.</div>
                            <div class="validity-tag">
                                Validity: {{ $validityDays }} Days (Valid Until: {{ $validUntil->format('d/m/Y') }})
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
                        <td style="width: 44%; font-style: italic; color: #16285A; font-weight: bold;">
                            Thank you for your inquiry! Valid for {{ $validityDays }} Days.
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
                                <div class="company-title">{{ config('shop.name', 'SEWANAGALA CERAMIC') }}</div>
                                <div class="company-tagline">{{ config('shop.tagline', 'Importers of Wall Tiles & Floor Tiles, Bathroom Sets, Bathroom Fittings, Glass Doors, Aluminium Doors, Borders & Sanitaryware') }}</div>
                                <div class="company-address">
                                    {{ config('shop.address', 'No 86, Delgahamuwa, Ibbagamuwa.') }}<br>
                                    <strong>Tel:</strong> {{ config('shop.phone', '0778186280 / 0778186280 / 0372259999') }} &nbsp;|&nbsp; <strong>WhatsApp:</strong> {{ config('shop.whatsapp', '0778186280') }}
                                </div>
                                <div class="quotation-badge">OFFICIAL QUOTATION</div>
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
                                            <td class="val" style="color: #CC0E11;">: <strong>{{ $validUntil->format('d/m/Y') }}</strong> <span style="font-size: 6px; color: #92400E;">({{ $validityDays }} Days)</span></td>
                                        </tr>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </table>
                    @else
                    <!-- 1b. Continuation Header (Page 2 onwards) -->
                    <div class="continuation-header-box">
                        <table class="continuation-table">
                            <tr>
                                <td>Quotation: <span>{{ $quotation->quotation_number }}</span></td>
                                <td>Customer: <span>{{ $billName }}</span></td>
                                <td>Date: <span>{{ $quotationDate->format('d/m/Y') }}</span></td>
                                <td>Valid: <span>{{ $validUntil->format('d/m/Y') }} ({{ $validityDays }} Days)</span></td>
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
                                            <span style="font-size: 6px; color: #6B7280;">({{ $item->product_code }})</span>
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
                        &gt;&gt; Continued on Page {{ $pageData['page_number'] + 1 }} of {{ $pageData['total_pages'] }}...
                    </div>
                    <div class="page-badge">
                        Page {{ $pageData['page_number'] }} of {{ $pageData['total_pages'] }}
                    </div>
                    @endif
                </td>
            </tr>
            @if($pageData['is_last'])
            <tr>
                <td class="main-invoice-footer-cell">
                    <!-- 3. Bottom Grid: Terms & Totals (On Final Page) -->
                    <table class="bottom-table">
                        <tr>
                            <td class="terms-col">
                                <div class="terms-note">QUOTATION VALIDITY: {{ $validityDays }} DAYS ONLY</div>
                                <div style="font-size: 6px; color: #4B5563;">This quotation is strictly valid for {{ $validityDays }} days from date of issue. Prices & discounts subject to confirmation thereafter.</div>
                                <div class="validity-tag">
                                    Validity: {{ $validityDays }} Days (Until {{ $validUntil->format('d/m/Y') }})
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
                            <td style="width: 44%; font-style: italic; color: #16285A; font-weight: bold;">
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
                </td>
            </tr>
            @endif
        </table>
    @endforeach
@endif

</body>
</html>
