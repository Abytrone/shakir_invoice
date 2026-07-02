<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $receipt->receipt_number }} - Shakir Dynamics</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Roboto, sans-serif;
            color: #374151;
            margin: 0;
            padding: 0;
        }

        .header {
            position: fixed;
            top: 20;
            left: 50;
            right: 50;
            height: 40mm;
            z-index: -1;
        }

        .footer {
            position: fixed;
            bottom: 5mm;
            left: 0;
            right: 0;
            height: 35mm;
            z-index: -1;
        }

        .receipt-content {
            padding-left: 50;
            padding-right: 50;
            box-sizing: border-box;
        }

        .title {
            font-size: 2.5rem;
            color: #f59e0b;
            font-weight: bold;
            margin-top: 0.5rem;
        }

        .receipt-meta {
            margin-top: 1.5rem;
            color: #4b5563;
            width: 45%;
            float: right;
        }

        .receipt-meta h1 {
            font-size: 1.25rem;
            font-weight: bold;
        }

        .receipt-meta p {
            font-size: 0.875rem;
        }

        .meta-title {
            color: #2563eb;
            font-weight: bold;
        }

        .bill-to {
            margin-top: 5rem;
        }

        .bill-to h2,
        .section-label {
            font-size: 1.1rem;
            color: #f59e0b;
            font-weight: bold;
        }

        .bill-to p {
            font-size: 0.9rem;
            line-height: 1.4;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            font-size: 0.9rem;
            table-layout: fixed;
            word-wrap: break-word;
        }

        th, td {
            border: 1px solid #e5e7eb;
            padding: 0.6rem 1rem;
            text-align: left;
        }

        th {
            background-color: #F2F6FFFF;
            font-weight: 700;
        }

        .totals {
            margin-top: 2rem;
            width: 50%;
            margin-left: auto;
            font-size: 0.9rem;
        }

        .totals .label {
            font-weight: 700;
            text-align: left;
        }

        .totals .value {
            text-align: right;
        }

        .footer-note {
            text-align: center;
            font-size: 0.85rem;
            margin: 1rem 0;
            color: #6b7280;
        }

        .text-sm { font-size: 0.875rem; }
        .font-bold { font-weight: 700; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-blue-600 { color: #2563eb; }
        .text-yellow-500 { color: #f59e0b; }
        .mb-4 { margin-bottom: 0.5rem; }
        .mb-5 { margin-bottom: 1rem; }
        .mt-6 { margin-top: 1.5rem; }

        .sign {
            padding-right: 50;
            padding-left: 50;
            margin-top: 2rem;
        }

        @page {
            margin: 45mm 15mm 35mm 15mm;
            size: A4;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ public_path('images/letterhead_items_header.png') }}" alt="Header" style="width: 100%; height: auto;">
        <div class="title">RECEIPT</div>
    </div>

    <div class="footer">
        <div class="footer-note">Thank you for your business!</div>
        <img src="{{ public_path('images/letterhead_items_footer.png') }}" alt="Footer" style="width: 100%; height: auto;">
    </div>

    <div style="height: 40mm;"></div>

    <div class="receipt-content">
        <div class="receipt-meta">
            <div style="text-align: left;">
                <h1 class="mb-5"><span class="meta-title">RECEIPT</span> #{{ $receipt->receipt_number }}</h1>
                <p class="text-sm mb-4"><span class="meta-title">DATE: </span>{{ $receipt->receipt_date->format('d-m-Y') }}</p>
            </div>
        </div>

        <div class="bill-to">
            <h2>RECEIVED FROM:</h2>
            <p>{{ $receipt->received_from_name }}</p>
            @if($receipt->received_from_address)
                <p>{{ $receipt->received_from_address }}</p>
            @endif
            @if($receipt->received_from_email)
                <p>{{ $receipt->received_from_email }}</p>
            @endif
            @if($receipt->received_from_phone)
                <p>{{ $receipt->received_from_phone }}</p>
            @endif
        </div>

        <table>
            <thead>
                <span class="section-label">FOR:</span>
                <tr>
                    <th class="text-center" style="width: 10%;">No.</th>
                    <th class="text-center" style="width: 40%;">Item</th>
                    <th class="text-center" style="width: 10%;">Qty</th>
                    <th class="text-center">Price (GHC)</th>
                    <th class="text-center">Total (GHC)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td class="text-center">{{ $loop->iteration }}</td>
                        <td>{{ $item['product_name'] ?? 'Item' }}</td>
                        <td class="text-center">{{ $item['quantity'] ?? 0 }}</td>
                        <td class="text-right">{{ number_format((float)($item['unit_price'] ?? 0), 2) }}</td>
                        <td class="text-right">{{ number_format((float)($item['total'] ?? (($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0))), 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-6 font-bold" style="line-height: 40px;">
            <span class="text-yellow-500">AMOUNT RECEIVED IN WORDS: </span>
            <span style="display: inline; border-bottom: 1px solid #f59e0b; padding-bottom: 2px;">
                {{ $amountInWords }}
            </span>
        </div>

        <table class="totals">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="value">GHC {{ number_format($receipt->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Discount ({{ $receipt->discount_rate }}%):</td>
                <td class="value">-GHC {{ number_format($receipt->discount_amount, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Tax ({{ $receipt->tax_rate }}%):</td>
                <td class="value">GHC {{ number_format($receipt->tax_amount, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Total:</td>
                <td class="value"><strong>GHC {{ number_format($receipt->total, 2) }}</strong></td>
            </tr>
        </table>

        @if($receipt->notes)
            <div class="mt-6">
                <span class="section-label">NOTE:</span>
                <p>{{ $receipt->notes }}</p>
            </div>
        @endif

        <div class="sign">
            <img src="{{ public_path('images/sign.png') }}" alt="Signature" style="width: 15%; height: auto;">
            <div style="width: 30%; border: 1px solid #2563eb;"></div>
            <div class="text-sm text-blue-600">Authorized Signatory</div>
        </div>
    </div>
</body>
</html>
