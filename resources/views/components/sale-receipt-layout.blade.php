@php
$paymentMethodLabels = [
    'cash' => 'Cash',
    'cheque' => 'Cheque',
    'bank_transfer' => 'Bank Transfer',
    'credit_card' => 'Credit Card',
];

$paymentMethods = $sale->payments
    ->pluck('payment_method')
    ->filter()
    ->unique()
    ->values();

if ($paymentMethods->count() > 1) {
    $methodText = $paymentMethods
        ->map(fn ($method) => $paymentMethodLabels[$method] ?? ucwords(str_replace('_', ' ', $method)))
        ->implode(' + ');
    $paymentLabel = 'Multiple (' . $methodText . ')';
} elseif ($paymentMethods->count() === 1) {
    $singleMethod = $paymentMethods->first();
    $paymentLabel = $paymentMethodLabels[$singleMethod] ?? ucwords(str_replace('_', ' ', $singleMethod));
} else {
    $paymentLabel = ($sale->due_amount ?? 0) > 0 ? 'Due' : 'Cash';
}

$billName = trim((string) (optional($sale->customer)->name ?? ''));
$billAddress = trim((string) (optional($sale->customer)->address ?? ''));
$billPhone = trim((string) (optional($sale->customer)->phone ?? ''));
if ($billName === '' || is_numeric($billName)) {
    $billName = 'Walking Customer';
}

$displayDiscount = max(0, (float) ($sale->discount_amount ?? 0));
$returnItems = $sale->returns ?? collect();
$returnTotal = (float) $returnItems->sum('total_amount');
$netTotal = max(0, (float) $sale->total_amount - $returnTotal);
$displayPaid = min($sale->payments->sum('amount'), $netTotal);
$displayBalance = max(0, $netTotal - $displayPaid);
$itemCount = count($sale->items);
$invFiller = max(0, 7 - $itemCount);

$logoPath = public_path('images/logo.png');
$logoSrc = asset('images/logo.png');
if (file_exists($logoPath)) {
    $logoSrc = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
}
@endphp

<style>
    #saleReceiptPrintContent .srp-canvas {
        background: #F1F5F9;
        padding: 24px;
        box-sizing: border-box;
        width: 100%;
    }

    #saleReceiptPrintContent .srp-container {
        --brand-navy: #16285A;
        --brand-orange: #CC0E11;
        --brand-border: #16285A;
        width: 100%;
        max-width: 900px;
        margin: 0 auto;
        background: #ffffff;
        border: 1px solid #CBD5E1;
        border-radius: 6px;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        font-family: 'Segoe UI', system-ui, Arial, sans-serif;
        color: #111827;
        padding: 24px 28px;
        box-sizing: border-box;
        position: relative;
    }

    #saleReceiptPrintContent .srp-watermark {
        position: absolute;
        top: 52%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 260px;
        opacity: 0.04;
        pointer-events: none;
        z-index: 0;
    }

    /* ── Header Section ── */
    #saleReceiptPrintContent .srp-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 16px;
        position: relative;
        z-index: 1;
    }

    #saleReceiptPrintContent .srp-company-info {
        flex: 1;
    }

    #saleReceiptPrintContent .srp-company-logo {
        max-height: 55px;
        width: auto;
        object-fit: contain;
        margin-bottom: 6px;
    }

    #saleReceiptPrintContent .srp-company-text h2 {
        font-size: 22px;
        font-weight: 800;
        color: var(--brand-navy);
        margin: 0 0 4px 0;
        letter-spacing: -0.01em;
        text-transform: uppercase;
    }

    #saleReceiptPrintContent .srp-company-tagline {
        font-size: 11px;
        font-weight: 600;
        color: #4B5563;
        margin: 0 0 6px 0;
        line-height: 1.35;
    }

    #saleReceiptPrintContent .srp-company-contact {
        font-size: 11px;
        color: #374151;
        line-height: 1.45;
        margin: 0;
    }

    /* ── Customer & Invoice Details Box (Navy outline box matching Image 2) ── */
    #saleReceiptPrintContent .srp-details-box {
        width: 320px;
        flex-shrink: 0;
        border: 1.5px solid var(--brand-navy);
        border-radius: 8px;
        padding: 10px 14px;
        background: #ffffff;
        box-sizing: border-box;
    }

    #saleReceiptPrintContent .srp-details-table {
        width: 100%;
        border-collapse: collapse;
    }

    #saleReceiptPrintContent .srp-details-table td {
        border: none !important;
        padding: 3px 0;
        font-size: 11.5px;
        vertical-align: top;
    }

    #saleReceiptPrintContent .srp-details-table td.lbl {
        width: 85px;
        font-weight: 700;
        color: #16285A;
        white-space: nowrap;
    }

    #saleReceiptPrintContent .srp-details-table td.val {
        font-weight: 700;
        color: #111827;
    }

    /* ── Items Table Box (Navy outline box matching Image 2) ── */
    #saleReceiptPrintContent .srp-table-wrap {
        border: 1.5px solid var(--brand-navy);
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 16px;
        position: relative;
        z-index: 1;
        min-height: 240px;
        background: #ffffff;
    }

    #saleReceiptPrintContent .srp-items-table {
        width: 100%;
        border-collapse: collapse;
    }

    #saleReceiptPrintContent .srp-items-table thead th {
        background: var(--brand-orange);
        color: #ffffff;
        font-size: 11.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 8px 10px;
        border: none !important;
    }

    #saleReceiptPrintContent .srp-items-table tbody td {
        border: none !important;
        padding: 6px 10px;
        font-size: 11.5px;
        color: #1F2937;
        vertical-align: top;
    }

    #saleReceiptPrintContent .srp-items-table .blank-row td {
        height: 22px;
        padding: 0;
    }

    /* ── Bottom Section: Terms on Left, Totals on Right ── */
    #saleReceiptPrintContent .srp-footer-grid {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 20px;
        position: relative;
        z-index: 1;
    }

    #saleReceiptPrintContent .srp-terms-box {
        flex: 1;
        font-size: 10.5px;
        line-height: 1.6;
        color: #374151;
    }

    #saleReceiptPrintContent .srp-sinhala-note {
        font-size: 11.5px;
        font-weight: 700;
        color: var(--brand-navy);
        margin-bottom: 3px;
    }

    #saleReceiptPrintContent .srp-english-note {
        font-size: 10.5px;
        color: #4B5563;
        margin-bottom: 8px;
    }

    /* ── Totals Box (Navy outline box matching Image 2) ── */
    #saleReceiptPrintContent .srp-totals-box {
        width: 270px;
        flex-shrink: 0;
        border: 1.5px solid var(--brand-navy);
        border-radius: 8px;
        overflow: hidden;
        background: #ffffff;
        padding: 4px 0;
    }

    #saleReceiptPrintContent .srp-totals-table {
        width: 100%;
        border-collapse: collapse;
    }

    #saleReceiptPrintContent .srp-totals-table td {
        padding: 5px 14px;
        font-size: 11.5px;
        border: none !important;
    }

    #saleReceiptPrintContent .srp-totals-table td.lbl {
        font-weight: 700;
        color: #16285A;
    }

    #saleReceiptPrintContent .srp-totals-table td.val {
        text-align: right;
        font-weight: 700;
        color: #111827;
    }

    #saleReceiptPrintContent .srp-totals-table tr.highlight td.val {
        color: var(--brand-orange);
        font-size: 12.5px;
    }

    /* ── Signatures Section ── */
    #saleReceiptPrintContent .srp-signatures {
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        gap: 15px;
        margin-top: 22px;
        position: relative;
        z-index: 1;
    }

    #saleReceiptPrintContent .srp-sig-item {
        width: 180px;
        text-align: center;
    }

    #saleReceiptPrintContent .srp-sig-line {
        border-top: 1.5px dashed #9CA3AF;
        margin-bottom: 5px;
    }

    #saleReceiptPrintContent .srp-sig-title {
        font-size: 10.5px;
        font-weight: 600;
        color: #4B5563;
    }

    #saleReceiptPrintContent .srp-thankyou {
        flex: 1;
        text-align: center;
        font-size: 11px;
        font-style: italic;
        font-weight: 700;
        color: var(--brand-navy);
    }

    @media print {
        #saleReceiptPrintContent .srp-canvas {
            background: #ffffff !important;
            padding: 0 !important;
        }

        #saleReceiptPrintContent .srp-container {
            border: none !important;
            box-shadow: none !important;
            padding: 0 !important;
            max-width: 100% !important;
        }
    }
</style>

<div class="srp-canvas">
    <div class="srp-container">
        <img class="srp-watermark" src="{{ $logoSrc }}" alt="" />

        <!-- 1. Header Section -->
        <div class="srp-header">
            <div class="srp-company-info">
                <img src="{{ $logoSrc }}" alt="{{ config('shop.name', 'THIHARIYA TILE CENTER') }}" class="srp-company-logo">
                <div class="srp-company-text">
                    <h2>{{ config('shop.name', 'THIHARIYA TILE CENTER') }}</h2>
                    <div class="srp-company-tagline">{{ config('shop.tagline', 'IMPORTERS & DEALERS IN WALL TILES, FLOOR TILES & SANITARYWARE Etc...') }}</div>
                    <div class="srp-company-contact">
                        {{ config('shop.address', 'N 122/1H, Kandy Road, Thihariya, Sri Lanka.') }}<br>
                        <strong>Tel :</strong> {{ config('shop.phone', '+0332 290 295') }} | <strong>WhatsApp :</strong> {{ config('shop.whatsapp', '+94 77 085 6464') }}
                    </div>
                </div>
            </div>

            <!-- 2. Customer & Invoice Info Box (Navy Outline Matching Image 2) -->
            <div class="srp-details-box">
                <table class="srp-details-table">
                    <tr>
                        <td class="lbl">Mr/Mrs</td>
                        <td class="val">: {{ $billName }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Address</td>
                        <td class="val">: {{ $billAddress !== '' ? $billAddress : 'xxxxx' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Tel</td>
                        <td class="val">: {{ $billPhone !== '' ? $billPhone : 'xxxxx' }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Date</td>
                        <td class="val">: {{ $sale->created_at->format('d/m/Y') }} {{ $sale->created_at->format('H:i') }}</td>
                    </tr>
                    <tr>
                        <td class="lbl">Invoice No</td>
                        <td class="val">: {{ $sale->invoice_number }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- 3. Items Table Box (Navy Outline & Solid Orange Header Matching Image 2) -->
        <div class="srp-table-wrap">
            <table class="srp-items-table">
                <thead>
                    <tr>
                        <th style="width: 45px; text-align: center;">NO</th>
                        <th style="text-align: left;">DESCRIPTION</th>
                        <th style="width: 90px; text-align: center;">SIZE</th>
                        <th style="width: 60px; text-align: center;">QTY</th>
                        <th style="width: 110px; text-align: right;">RATE (RS.)</th>
                        <th style="width: 90px; text-align: right;">DISCOUNT</th>
                        <th style="width: 120px; text-align: right;">AMOUNT (RS.)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sale->items as $index => $item)
                    @php
                        $itemSize = $item->product_model ?? (optional($item->product)->model ?? '-');
                        if (empty($item->product_id)) {
                            $itemSize = 'Service';
                        }
                        $lineTotal = ($item->unit_price - $item->discount_per_unit) * $item->quantity;
                    @endphp
                    <tr>
                        <td style="text-align: center;">{{ sprintf('%02d', $index + 1) }}</td>
                        <td style="text-align: left; font-weight: 600;">
                            {{ $item->product_name }}
                            @if($item->product_code && $item->product_code !== $item->product_name)
                                <span style="font-size: 10px; color: #6B7280;">({{ $item->product_code }})</span>
                            @endif
                        </td>
                        <td style="text-align: center;">{{ $itemSize ?: '-' }}</td>
                        <td style="text-align: center; font-weight: 600;">{{ $item->quantity }}</td>
                        <td style="text-align: right;">{{ number_format($item->unit_price, 2) }}</td>
                        <td style="text-align: right;">{{ $item->discount_per_unit > 0 ? number_format($item->discount_per_unit, 2) : '-' }}</td>
                        <td style="text-align: right; font-weight: 600;">{{ number_format($lineTotal, 2) }}</td>
                    </tr>
                    @endforeach

                    @for($f = 0; $f < $invFiller; $f++)
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

        @if($returnItems->count() > 0)
        <div class="srp-table-wrap srp-returns-wrap">
            <div style="padding: 8px 10px; color: #CC0E11; font-size: 12px; font-weight: 800;">RETURNED ITEMS</div>
            <table class="srp-items-table">
                <thead>
                    <tr>
                        <th style="width: 45px; text-align: center;">NO</th>
                        <th style="text-align: left;">PRODUCT</th>
                        <th style="width: 70px; text-align: center;">QTY</th>
                        <th style="width: 120px; text-align: right;">PRICE (RS.)</th>
                        <th style="width: 130px; text-align: right;">TOTAL (RS.)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($returnItems as $index => $return)
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td style="font-weight: 600;">{{ optional($return->product)->name ?? 'Returned item' }}</td>
                        <td style="text-align: center;">{{ $return->return_quantity }}</td>
                        <td style="text-align: right;">{{ number_format($return->selling_price, 2) }}</td>
                        <td style="text-align: right; font-weight: 600;">{{ number_format($return->total_amount, 2) }}</td>
                    </tr>
                    @endforeach
                    <tr>
                        <td colspan="4" style="text-align: right; font-weight: 800;">Returned Items Total</td>
                        <td style="text-align: right; font-weight: 800; color: #CC0E11;">Rs. {{ number_format($returnTotal, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        <!-- 4. Bottom Section: Terms on Left, Totals on Right -->
        <div class="srp-footer-grid">
            <div class="srp-terms-box">
                <div class="srp-sinhala-note">ඔබ රැගෙන යන භාණ්ඩ දින 7ක් ඇතුලත මාරුකර දෙනු ලැබේ. මුදල් ආපසු ගෙවනු නොලැබේ.</div>
                <div class="srp-english-note">Goods return will be accepted within 7 days only. Cash will not be refunded.</div>
                <div style="font-size: 11px; font-weight: 700; color: #16285A;">
                    Payment Mode: <span style="color: #111827; font-weight: 600;">{{ $paymentLabel }}</span>
                </div>
            </div>

            <div class="srp-totals-box">
                <table class="srp-totals-table">
                    <tr>
                        <td class="lbl">Total</td>
                        <td class="val">Rs. {{ number_format($sale->total_amount + $displayDiscount, 2) }}</td>
                    </tr>
                    @if($displayDiscount > 0)
                    <tr>
                        <td class="lbl">Discount</td>
                        <td class="val">- Rs. {{ number_format($displayDiscount, 2) }}</td>
                    </tr>
                    @endif
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
        </div>

        <!-- 5. Signatures -->
        <div class="srp-signatures">
            <div class="srp-sig-item">
                <div class="srp-sig-line"></div>
                <div class="srp-sig-title">Checked By</div>
            </div>
            <div class="srp-thankyou">
                Thank you for your business! We look forward to seeing you again.
            </div>
            
            <div class="srp-sig-item">
                <div class="srp-sig-line"></div>
                <div class="srp-sig-title">Authorised Signature</div>
            </div>
        </div>
    </div>
</div>
