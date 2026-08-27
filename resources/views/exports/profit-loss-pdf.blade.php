<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profit & Loss Statement</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #333; background: #fff; }
        .header { text-align: center; padding: 20px 0 10px; border-bottom: 2px solid #2d6a2d; margin-bottom: 20px; }
        .header h1 { font-size: 22px; color: #2d6a2d; margin-bottom: 4px; }
        .header p { font-size: 12px; color: #666; }
        .period { background: #f0faf0; border: 1px solid #c3e6cb; border-radius: 4px; padding: 8px 14px; margin-bottom: 18px; font-size: 11px; }
        .section-title { font-size: 13px; font-weight: bold; padding: 7px 10px; margin-bottom: 0; border-radius: 3px 3px 0 0; }
        .section-title.green  { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .section-title.red    { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .section-title.yellow { background: #fff3cd; color: #856404; border-left: 4px solid #ffc107; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        table th, table td { border: 1px solid #dee2e6; padding: 6px 10px; }
        table thead th { background: #f8f9fa; font-weight: bold; font-size: 11px; }
        table tbody tr:nth-child(even) { background: #fdfdfd; }
        .row-label { padding-left: 20px; }
        .value-col { text-align: right; font-weight: bold; min-width: 130px; }
        .net-row td { background: #d4edda; font-size: 13px; font-weight: bold; color: #155724; }
        .net-row.loss td { background: #f8d7da; color: #721c24; }
        .summary-box { display: inline-block; width: 48%; vertical-align: top; border: 1px solid #dee2e6; border-radius: 4px; padding: 10px 14px; margin-bottom: 14px; }
        .summary-box h3 { font-size: 11px; color: #666; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.5px; }
        .summary-box .val { font-size: 17px; font-weight: bold; color: #333; }
        .footer { margin-top: 20px; border-top: 1px solid #dee2e6; padding-top: 10px; text-align: center; font-size: 10px; color: #888; }
        .spacer { height: 8px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>Profit &amp; Loss Statement</h1>
        <p>Financial Overview &amp; Performance Analysis</p>
    </div>

    <div class="period">
        <strong>Report Period:</strong>
        @if($startDate && $endDate)
            {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} &ndash; {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
        @elseif($startDate)
            From {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} onwards
        @elseif($endDate)
            Up to {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
        @else
            Overall / All Data
        @endif
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Generated:</strong> {{ now()->format('d/m/Y H:i A') }}
    </div>

    {{-- Summary Boxes --}}
    <table>
        <tr>
            <td style="width:33%; border:1px solid #dee2e6; padding:12px; text-align:center;">
                <div style="font-size:10px;color:#666;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Total Revenue</div>
                <div style="font-size:18px;font-weight:bold;color:#198754;">Rs. {{ number_format($totalRevenue, 2) }}</div>
            </td>
            <td style="width:33%; border:1px solid #dee2e6; padding:12px; text-align:center;">
                <div style="font-size:10px;color:#666;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Total Expenses</div>
                <div style="font-size:18px;font-weight:bold;color:#dc3545;">Rs. {{ number_format($totalExpenses, 2) }}</div>
            </td>
            <td style="width:33%; border:1px solid #dee2e6; padding:12px; text-align:center;">
                <div style="font-size:10px;color:#666;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Net Profit / (Loss)</div>
                <div style="font-size:18px;font-weight:bold;color:{{ $netProfit >= 0 ? '#198754' : '#dc3545' }};">
                    Rs. {{ number_format($netProfit, 2) }}
                </div>
            </td>
        </tr>
    </table>

    {{-- P&L Statement Table --}}
    <table>
        <thead>
            <tr>
                <th style="width:70%;">Description</th>
                <th style="width:30%;text-align:right;">Amount (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            <tr style="background:#d4edda;">
                <td colspan="2" style="font-weight:bold;color:#155724;font-size:12px;">📈 GROSS SALES REVENUE</td>
            </tr>
            <tr>
                <td class="row-label">Total Sales Amount</td>
                <td class="value-col" style="color:#198754;">{{ number_format($incomeTotals['Total Sales Revenue'] ?? 0, 2) }}</td>
            </tr>
            @if(!empty($revenueBreakdown))
                @foreach($revenueBreakdown as $type => $amount)
                <tr>
                    <td class="row-label" style="padding-left:30px;">└─ {{ $type }}</td>
                    <td class="value-col" style="color:#198754;">{{ number_format($amount, 2) }}</td>
                </tr>
                @endforeach
            @endif

            <tr class="spacer"><td colspan="2" style="border:none;height:6px;"></td></tr>

            <tr style="background:#f8d7da;">
                <td colspan="2" style="font-weight:bold;color:#721c24;font-size:12px;">📦 COST OF GOODS SOLD (COGS)</td>
            </tr>
            <tr>
                <td class="row-label">Product Cost</td>
                <td class="value-col" style="color:#dc3545;">({{ number_format($totalCOGS, 2) }})</td>
            </tr>

            <tr class="spacer"><td colspan="2" style="border:none;height:6px;"></td></tr>

            <tr style="background:#fff3cd;">
                <td style="font-weight:bold;color:#856404;font-size:12px;">✅ NET REVENUE (Gross Sales − COGS)</td>
                <td class="value-col" style="color:#856404;">{{ number_format($totalRevenue, 2) }} <small>({{ $grossProfitPercentage }}%)</small></td>
            </tr>

            <tr class="spacer"><td colspan="2" style="border:none;height:6px;"></td></tr>

            <tr style="background:#f8d7da;">
                <td colspan="2" style="font-weight:bold;color:#721c24;font-size:12px;">🔄 PRODUCT RETURNS IMPACT</td>
            </tr>
            <tr>
                <td class="row-label">Return Amount (Selling Price)</td>
                <td class="value-col" style="color:#dc3545;">({{ number_format($totalReturns, 2) }})</td>
            </tr>
            <tr>
                <td class="row-label">Less: Return COGS</td>
                <td class="value-col" style="color:#dc3545;">+{{ number_format($totalReturnsCOGS, 2) }}</td>
            </tr>
            <tr style="background:#ffe7e7;">
                <td class="row-label" style="font-weight:bold;color:#721c24;">Net Loss from Returns</td>
                <td class="value-col" style="color:#dc3545;">({{ number_format($returnImpact, 2) }})</td>
            </tr>

            <tr class="spacer"><td colspan="2" style="border:none;height:6px;"></td></tr>

            <tr style="background:#fff3cd;">
                <td colspan="2" style="font-weight:bold;color:#856404;font-size:12px;">💼 OPERATING EXPENSES</td>
            </tr>
            <tr>
                <td class="row-label">Total Expenses</td>
                <td class="value-col" style="color:#856404;">{{ number_format($totalExpenses, 2) }}</td>
            </tr>
            @if(!empty($expenseBreakdown))
                @foreach($expenseBreakdown as $category => $details)
                <tr>
                    <td class="row-label" style="padding-left:30px;">└─ {{ $category }}</td>
                    <td class="value-col" style="color:#856404;">{{ number_format($details['amount'] ?? $details, 2) }}</td>
                </tr>
                @endforeach
            @endif

            <tr class="spacer"><td colspan="2" style="border:none;height:6px;"></td></tr>

            <tr class="{{ $netProfit >= 0 ? 'net-row' : 'net-row loss' }}">
                <td>💰 NET PROFIT / (LOSS)</td>
                <td class="value-col">{{ number_format($netProfit, 2) }} <small>({{ $netProfitPercentage }}%)</small></td>
            </tr>
        </tbody>
    </table>

    {{-- Monthly Trends --}}
    @if(!empty($monthlyTrends))
    <br>
    <div style="font-size:13px;font-weight:bold;margin-bottom:8px;color:#333;">📅 Monthly Trends</div>
    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th style="text-align:right;">Revenue (Rs.)</th>
                <th style="text-align:right;">COGS (Rs.)</th>
                <th style="text-align:right;">Expenses (Rs.)</th>
                <th style="text-align:right;">Profit (Rs.)</th>
                <th style="text-align:right;">Margin %</th>
            </tr>
        </thead>
        <tbody>
            @foreach($monthlyTrends as $trend)
            <tr>
                <td>{{ $trend['month'] }}</td>
                <td style="text-align:right;color:#198754;">{{ number_format($trend['revenue'], 2) }}</td>
                <td style="text-align:right;color:#856404;">{{ number_format($trend['cogs'], 2) }}</td>
                <td style="text-align:right;color:#dc3545;">{{ number_format($trend['expenses'], 2) }}</td>
                <td style="text-align:right;font-weight:bold;color:{{ $trend['profit'] >= 0 ? '#198754' : '#dc3545' }};">{{ number_format($trend['profit'], 2) }}</td>
                <td style="text-align:right;">{{ $trend['revenue'] > 0 ? number_format(($trend['profit'] / $trend['revenue']) * 100, 1) : 0 }}%</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        This report was auto-generated by {{ config('shop.name', 'Thihariya Tile Center') }} &bull; {{ now()->format('d/m/Y H:i A') }}
    </div>

</body>
</html>
