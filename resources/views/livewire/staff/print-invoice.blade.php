<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $sale->invoice_number }}</title>
    <style>
        /* Reset and Base Setup */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            background: #f4f7f6; 
            font-family: 'Courier New', Courier, monospace; 
            font-size: 11pt; 
            color: #000;
        }

        /* Screen controls for calibration */
        .controls {
            background: #2c3e50;
            padding: 15px;
            color: #fff;
            text-align: center;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            font-family: sans-serif;
        }

        .btn {
            display: inline-block; padding: 10px 25px; margin: 0 10px; cursor: pointer;
            border-radius: 4px; border: none; font-size: 14px; text-decoration: none; 
            font-weight: bold; transition: opacity 0.2s;
        }
        .btn-print { background: #27ae60; color: #fff; }
        .btn-close { background: #c0392b; color: #fff; }
        .btn:hover { opacity: 0.9; }

        /* The Paper Canvas - Fits 9.5in x 5.5in */
        .page {
            width: 9.5in;
            height: 5.5in;
            background: #fff;
            margin: 100px auto; 
            position: relative;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            overflow: hidden;
            border: 1px dashed #ccc;
        }

        /* Print Specific Logic */
        @media print {
            body { background: transparent !important; }
            .controls { display: none !important; }
            .page { 
                margin: 0 !important; 
                box-shadow: none !important; 
                border: none !important;
                page-break-after: always;
            }
            @page {
                size: 9.5in 5.5in;
                margin: 0;
            }
        }

        /* Absolute Positioning System */
        .data-field { 
            position: absolute; 
            white-space: nowrap; 
        }

        /**
         * CALIBRATION SECTION - SYNCED RECALIBRATION: HEADERS REVERTED | TOTALS LEFT
         */
        
        /* Header Box - Right Side (REVERTED to 185mm) */
        .date             { top: 14mm; left: 185mm; }
        .invoice-no       { top: 21mm; left: 185mm; }
        .sales-rep        { top: 27mm; left: 185mm; }
        .payment-method   { top: 33mm; left: 185mm; }
        
        /* Customer Box - Left Side */
        .customer-name    { top: 23mm; left: 35mm; width: 105mm; }
        .customer-address { top: 27.5mm; left: 35mm; width: 150mm; white-space: normal; line-height: 0.8; }
        .customer-phone   { top: 32mm; left: 35mm; }

        /* Item Row Layout - AMOUNTS KEPT AT 182mm */
        .item-row      { position: absolute; width: 9.5in; height: 6mm; left: 0; }
        .col-code      { position: absolute; left: 11mm; width: 25mm; }
        .col-desc      { position: absolute; left: 39mm; width: 40mm; overflow: hidden; }
        .col-qty       { position: absolute; left: 112mm; width: 15mm; text-align: right; }
        .col-price     { position: absolute; left: 132mm; width: 30mm; text-align: right; }
        .col-discount  { position: absolute; left: 169mm; width: 15mm; text-align: right; }
        .col-total     { position: absolute; left: 182mm; width: 35mm; text-align: right; }

        /* Summary Area - KEPT AT 182mm */
        .subtotal      { top: 100mm; left: 182mm; text-align: right; width: 35mm; }
        .discount      { top: 104.5mm; left: 182mm; text-align: right; width: 35mm; }
        .grand-total   { top: 109mm; left: 182mm; text-align: right; width: 35mm; font-weight: bold; font-size: 11pt; }
        
        .paid-label    { top: 113.5mm; left: 42mm; border-bottom: none !important; }
        .paid-amount   { top: 113.5mm; left: 182mm; text-align: right; width: 35mm; }
        .balance-label { top: 118mm; left: 42mm; font-weight: bold; }
        .balance-amount{ top: 118mm; left: 182mm; text-align: right; width: 35mm; font-weight: bold; }

    </style>
 head>
<body>

    @php
        // Prepare display values
        $billName = trim((string) (optional($sale->customer)->name ?? 'Walk-in Customer'));
        $billAddress = trim((string) (optional($sale->customer)->address ?? ''));
        $billPhone = trim((string) (optional($sale->customer)->phone ?? ''));
        
        // FORMAT INVOICE NUMBER: INV-20260403-0068 -> INV-2026-0068
        $invParts = explode('-', $sale->invoice_number);
        if (count($invParts) === 3) {
            $formattedInvoice = $invParts[0] . '-' . substr($invParts[1], 0, 4) . '-' . $invParts[2];
        } else {
            $formattedInvoice = $sale->invoice_number;
        }

        $paymentMethodLabels = [
            'cash' => 'Cash',
            'cheque' => 'CHQ',
            'bank_transfer' => 'BT',
            'credit_card' => 'CC',
        ];

        $paymentMethods = $sale->payments
            ->pluck('payment_method')
            ->filter()
            ->unique()
            ->values();

        if ($paymentMethods->count() > 1) {
            $methodText = $paymentMethods
                ->map(fn ($method) => $paymentMethodLabels[$method] ?? ucwords(str_replace('_', ' ', $method)))
                ->implode('+');
            $paymentLabel = $methodText;
        } elseif ($paymentMethods->count() === 1) {
            $singleMethod = $paymentMethods->first();
            $paymentLabel = $paymentMethodLabels[$singleMethod] ?? ucwords(str_replace('_', ' ', $singleMethod));
        } else {
            $paymentLabel = ($sale->due_amount ?? 0) > 0 ? 'Due' : 'Cash';
        }

        $totalPaid = $sale->payments->sum('amount');
        $balance = max(0, $sale->total_amount - $totalPaid);

        // TABLE ROW CALIBRATION
        $rowStartY = 47; 
        $rowHeight = 6; 
    @endphp

    <div class="controls no-print">
        <h2 style="margin-bottom: 10px;">Staff Dot-Matrix Preview</h2>
        <button class="btn btn-print" onclick="window.print()">START PRINTING</button>
        <button class="btn btn-close" onclick="window.close()">EXIT PREVIEW</button>
    </div>

    <div class="page">
        <!-- HEADER SECTION -->
        <div class="data-field date">{{ $sale->created_at->format('d/m/Y') }}</div>
        <div class="data-field invoice-no">{{ $formattedInvoice }}</div>
        <div class="data-field sales-rep">{{ substr($sale->user->name ?? '-', 0, 15) }}</div>
        <div class="data-field payment-method">{{ $paymentLabel }}</div>
        
        <div class="data-field customer-name">{{ $billName }}</div>
        <div class="data-field customer-address">{{ substr($billAddress, 0, 100) }}</div>
        <div class="data-field customer-phone">{{ $billPhone }}</div>

        <!-- ITEMS SECTION -->
        @foreach($sale->items as $index => $item)
            @php $currentY = $rowStartY + ($index * $rowHeight); @endphp
            <div class="item-row" style="top: {{ $currentY }}mm;">
                <span class="col-code">{{ $item->product_code }}</span>
                <span class="col-desc">{{ substr($item->product_name, 0, 30) }}</span>
                <span class="col-qty">{{ number_format($item->quantity, 0) }}</span>
                <span class="col-price">{{ number_format($item->unit_price, 2) }}</span>
                <span class="col-discount">@if($item->discount_per_unit > 0){{ number_format($item->discount_per_unit, 2) }}@endif</span>
                <span class="col-total">{{ number_format($item->total, 2) }}</span>
            </div>
        @endforeach

        <!-- FOOTER / SUMMARY SECTION -->
        <div class="data-field subtotal">{{ number_format($sale->subtotal, 2) }}</div>
        <div class="data-field discount">{{ number_format($sale->discount_amount, 2) }}</div>
        <div class="data-field grand-total">{{ number_format($sale->total_amount, 2) }}</div>
        <div class="data-field paid-amount">{{ number_format($totalPaid, 2) }}</div>
        <div class="data-field balance-amount">{{ number_format($balance, 2) }}</div>
    </div>

</body>
</html>