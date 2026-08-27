<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $sale->invoice_number }}</title>
    <style>
        @page {
            size: A5 landscape;
            margin: 2mm;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        #saleReceiptPrintContent {
            width: 100%;
            margin: 0;
            padding: 0;
        }
    </style>
</head>

<body>
    <div id="saleReceiptPrintContent">
        @include('components.sale-receipt-layout', ['sale' => $sale])
    </div>
</body>

</html>