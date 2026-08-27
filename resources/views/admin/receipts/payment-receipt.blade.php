<!DOCTYPE html>
<html>

<head>
    <title>Payment Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #16285A;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #CC0E11;
        }

        .receipt-title {
            font-size: 20px;
            margin: 10px 0;
            color: #16285A;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .details-table td,
        .details-table th {
            padding: 8px;
            border: 1px solid #ddd;
        }

        .details-table .label {
            font-weight: bold;
            background-color: #f8f9fa;
            width: 30%;
        }

        .details-table th {
            background-color: #16285A;
            color: white;
            text-align: left;
        }

        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }

        .signature-area {
            margin-top: 60px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .allocations-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .allocations-table th,
        .allocations-table td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .allocations-table th {
            background-color: #16285A;
            color: white;
        }

        .info-badge {
            display: inline-block;
            padding: 2px 6px;
            background-color: #fdebed;
            color: #CC0E11;
            font-size: 10px;
            border-radius: 3px;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="company-name">{{ config('shop.name') }}</div>
        <div>{{ config('shop.address') }}</div>
        <div>Tel: {{ config('shop.phone') }}</div>
        <div class="receipt-title">PAYMENT RECEIPT</div>
    </div>

    <table class="details-table">
        <tr>
            <td class="label">Receipt ID</td>
            <td>#{{ $payment->payment_reference ?: $payment->id }}</td>
            <td class="label">Payment Date</td>
            <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td class="label">Customer Name</td>
            <td>{{ $customer->name }}</td>
            <td class="label">Phone</td>
            <td>{{ $customer->phone }}</td>
        </tr>
        <tr>
            <td class="label">Address</td>
            <td colspan="3">{{ $customer->address ?: '-' }}</td>
        </tr>
    </table>

    {{-- Payment Summary Table --}}
    <div style="margin: 20px 0;">
        <h3 style="margin-bottom: 10px;">Payment Details:</h3>
        <table class="allocations-table">
            <thead>
                <tr>
                    <th width="30%">Method</th>
                    <th>Details</th>
                    <th width="25%" class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $p)
                <tr>
                    <td class="text-capitalize">{{ str_replace('_', ' ', $p->payment_method) }}</td>
                    <td>
                        @if($p->payment_method === 'cheque')
                            @foreach($p->cheques as $cheque)
                                <div>Chq: {{ $cheque->cheque_number }} ({{ $cheque->bank_name }}) - {{ \Carbon\Carbon::parse($cheque->cheque_date)->format('d/m/Y') }}</div>
                            @endforeach
                        @elseif($p->payment_method === 'bank_transfer')
                            <div>{{ $p->bank_name }} - Ref: {{ $p->transfer_reference }}</div>
                        @else
                            <div>Standard cash payment</div>
                        @endif
                    </td>
                    <td class="text-right">Rs.{{ number_format($p->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2" class="text-right">Total Paid</td>
                    <td class="text-right">Rs.{{ number_format($total_amount_paid, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Payment Allocation Details --}}
    @if($allocations && count($allocations) > 0)
    <div style="margin: 20px 0;">
        <h3 style="margin-bottom: 10px;">Payment Allocation:</h3>
        <table class="allocations-table">
            <thead>
                <tr>
                    <th>Invoice Number</th>
                    <th>Invoice Total</th>
                    <th class="text-right">Allocated Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allocations as $allocation)
                <tr>
                    <td>
                        {{ $allocation->invoice_number }}
                        @if($allocation->return_amount > 0)
                        <span class="info-badge">Returns: Rs.{{ number_format($allocation->return_amount, 2) }}</span>
                        @endif
                    </td>
                    <td>
                        @if($allocation->return_amount > 0)
                        <span style="text-decoration: line-through; color: #999;">Rs.{{ number_format($allocation->total_amount, 2) }}</span>
                        <br>
                        <strong>Rs.{{ number_format($allocation->adjusted_total, 2) }}</strong>
                        <span style="font-size: 10px; color: #666;">(Adjusted)</span>
                        @else
                        Rs.{{ number_format($allocation->total_amount, 2) }}
                        @endif
                    </td>
                    <td class="text-right">Rs.{{ number_format($allocation->allocated_amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <table class="details-table">
        <tr class="total-row">
            <td class="label">Total Amount Paid</td>
            <td class="text-right">Rs.{{ number_format($total_amount_paid, 2) }}</td>
        </tr>
    </table>

    @if($payment->notes)
    <div style="margin-top: 20px;">
        <strong>Notes:</strong>
        <p>{{ $payment->notes }}</p>
    </div>
    @endif

    <div class="signature-area">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;">
                    <div style="border-top: 1px solid #333; padding-top: 10px;">
                        Customer Signature
                    </div>
                </td>
                <td style="width: 50%;">
                    <div style="border-top: 1px solid #333; padding-top: 10px;">
                        Received By: {{ $received_by }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>Thank you for your payment!</p>
        <p>Generated on: {{ now()->format('d/m/Y h:i A') }}</p>
    </div>
</body>

</html>
