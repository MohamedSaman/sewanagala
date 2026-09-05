<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Payment Receipt - #{{ $payment->id }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
            background: white;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }

        .company-info {
            margin-bottom: 5px;
        }

        .receipt-title {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
            color: #198754;
        }

        .receipt-info {
            margin-bottom: 20px;
        }

        .section {
            margin-bottom: 15px;
        }

        .section-title {
            font-weight: bold;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table th {
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            font-weight: bold;
        }

        table td {
            border: 1px solid #ddd;
            padding: 8px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }

        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        hr {
            border: none;
            border-top: 1px solid #ddd;
            margin: 15px 0;
        }

        .status-paid {
            color: #198754;
            font-weight: bold;
        }

        .status-pending {
            color: #fd7e14;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        {{-- Header --}}
        <div class="header">
            <div class="company-info">
                <h2> {{ config('shop.name') }}</h2>
            </div>
            <div class="receipt-title">PAYMENT RECEIPT</div>
        </div>

        @php
            $isGrouped = isset($payments) && $payments instanceof \Illuminate\Support\Collection;
            $paymentList = $isGrouped ? $payments : collect([$payment]);
            $totalAmountPaid = $paymentList->sum('amount');
            $allAllocations = $paymentList->pluck('allocations')->flatten();
            $paymentMethods = $paymentList->pluck('payment_method')->unique()->map(function($m) {
                return strtoupper(str_replace('_', ' ', $m));
            })->join(', ');
        @endphp

        {{-- Receipt Information --}}
        <div class="receipt-info">
            <table class="table table-bordered">
                <tr>
                    <td width="50%">
                        <strong>Receipt #:</strong> {{ $payment->payment_reference ?: $payment->id }}<br>
                        <strong>Payment Date:</strong> {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') : '-' }}<br>
                        <strong>Generated On:</strong> {{ now()->format('d/m/Y H:i') }}
                    </td>
                    <td width="50%">
                        <strong>Payment Method:</strong> {{ $paymentMethods }}<br>
                        <strong>Status:</strong>
                        <span class="status-{{ $payment->status }}">{{ strtoupper($payment->status ?? 'PAID') }}</span><br>
                        @if($payment->payment_reference)
                        <strong>Reference:</strong> {{ $payment->payment_reference }}
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        {{-- Supplier Information --}}
        <div class="section">
            <div class="section-title">SUPPLIER INFORMATION</div>
            <table class="table table-bordered">
                <tr>
                    <td width="30%"><strong>Name:</strong></td>
                    <td>{{ $payment->supplier->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Phone:</strong></td>
                    <td>{{ $payment->supplier->phone ?: ($payment->supplier->contact ?: 'N/A') }}</td>
                </tr>
                <tr>
                    <td><strong>Email:</strong></td>
                    <td>{{ $payment->supplier->email ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <td><strong>Address:</strong></td>
                    <td>{{ $payment->supplier->address ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        {{-- Payment Details --}}
        <div class="section">
            <div class="section-title">PAYMENT DETAILS</div>
            <table class="table table-bordered">
                <tr>
                    <td width="30%"><strong>Total Amount Paid:</strong></td>
                    <td class="text-right"><strong>Rs. {{ number_format($totalAmountPaid, 2) }}</strong></td>
                </tr>
                @foreach($paymentList as $p)
                    @if($p->payment_method === 'cheque')
                    <tr>
                        <td><strong>Cheque (Rs. {{ number_format($p->amount, 2) }}):</strong></td>
                        <td>
                            <div><strong>Cheque No:</strong> {{ $p->cheque_number ?? '-' }}</div>
                            <div><strong>Bank:</strong> {{ $p->bank_name ?? '-' }}</div>
                            <div><strong>Date:</strong> {{ $p->cheque_date ? \Carbon\Carbon::parse($p->cheque_date)->format('d/m/Y') : 'N/A' }}</div>
                        </td>
                    </tr>
                    @elseif($p->payment_method === 'bank_transfer')
                    <tr>
                        <td><strong>Bank Transfer (Rs. {{ number_format($p->amount, 2) }}):</strong></td>
                        <td>
                            <div><strong>Bank:</strong> {{ $p->bank_name ?? '-' }}</div>
                            <div><strong>Transaction Ref:</strong> {{ $p->bank_transaction ?? '-' }}</div>
                        </td>
                    </tr>
                    @elseif($p->payment_method === 'cash' && $paymentList->count() > 1)
                    <tr>
                        <td><strong>Cash:</strong></td>
                        <td>Rs. {{ number_format($p->amount, 2) }}</td>
                    </tr>
                    @endif
                @endforeach
            </table>
        </div>

        {{-- Payment Allocation --}}
        @if($allAllocations && count($allAllocations) > 0)
        <div class="section">
            <div class="section-title">PAYMENT ALLOCATION</div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Order Code</th>
                        <th class="text-right">Due Amount</th>
                        <th class="text-right">Paid Amount</th>
                        <th class="text-right">Remaining</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allAllocations as $allocation)
                    @php
                    $order = $allocation->order;
                    $remaining = $order ? $order->due_amount : 0;
                    $paidAmount = $allocation->allocated_amount;
                    @endphp
                    <tr>
                        <td><strong>{{ $order ? $order->order_code : 'N/A' }}</strong></td>
                        <td class="text-right">Rs. {{ number_format(($order ? $order->due_amount : 0) + $paidAmount, 2) }}</td>
                        <td class="text-right">Rs. {{ number_format($paidAmount, 2) }}</td>
                        <td class="text-right">Rs. {{ number_format($remaining, 2) }}</td>
                        <td>
                            @if($remaining == 0)
                            <span class="status-paid">FULLY PAID</span>
                            @else
                            <span class="status-pending">PARTIAL PAID</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td><strong>TOTAL</strong></td>
                        <td class="text-right"><strong>Rs. {{ number_format($allAllocations->sum(function($alloc) { return ($alloc->order ? $alloc->order->due_amount : 0) + $alloc->allocated_amount; }), 2) }}</strong></td>
                        <td class="text-right"><strong>Rs. {{ number_format($allAllocations->sum('allocated_amount'), 2) }}</strong></td>
                        <td class="text-right"><strong>Rs. {{ number_format($allAllocations->sum(function($alloc) { return $alloc->order ? $alloc->order->due_amount : 0; }), 2) }}</strong></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
        @else
        <div class="section">
            <div class="section-title">PAYMENT ALLOCATION</div>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th class="text-right">Paid Amount</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Opening Balance / Previous Balance Payment</strong></td>
                        <td class="text-right"><strong>Rs. {{ number_format($totalAmountPaid, 2) }}</strong></td>
                        <td class="text-center"><span class="status-paid">PAID</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        @endif

        {{-- Notes --}}
        @php
            $combinedNotes = $paymentList->pluck('notes')->filter()->join(', ');
        @endphp
        @if($combinedNotes)
        <div class="section">
            <div class="section-title">NOTES</div>
            <p>{{ $combinedNotes }}</p>
        </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            <p>This is a computer-generated receipt. No signature required.</p>
            <p>Thank you for your payment!</p>
            <p>Generated on: {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>

</html>