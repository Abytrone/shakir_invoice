<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ $payment->reference_number }} - Shakir Dynamics</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
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

        .invoice-content {
            /* padding-top: 35mm;
            padding-bottom: 35mm; */
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

        .invoice-meta {
            margin-top: 1.5rem;
            color: #4b5563;
            width: 40%;
            float: right;
        }

        .invoice-meta h1 {
            font-size: 1.25rem;
            font-weight: bold;
        }

        .invoice-meta p {
            font-size: 0.875rem;
        }

        .meta-title {
            color: #2563eb;
            font-weight: bold;
        }

        .bill-to {
            margin-top: 5rem;
        }

        .bill-to h2 {
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
            margin-top: 2rem;
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

        .totals td {
            padding: 0.6rem 1rem;
        }

        .totals .label {
            font-weight: 700;
            text-align: left;
        }

        .totals .value {
            text-align: right;
        }

        .text-green {
            color: #10b981;
        }

        .text-red {
            color: #ef4444;
        }

        .footer-note {
            text-align: center;
            font-size: 0.85rem;
            margin: 1rem 0;
            color: #6b7280;
        }

        @page {
            margin: 45mm 15mm 35mm 15mm; /* top, right, bottom, left */
            size: A4;
        }

        @media print {
            body, .invoice-container {
                box-shadow: none;
                border-radius: 0;
            }

            .footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
            }
        }

        .w-full { width: 100%; }
        .max-w-4xl { max-width: 56rem; }
        .bg-white { background-color: white; }
        .shadow-lg { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .rounded-lg { border-radius: 8px; }
        .p-6 { padding: 1.5rem; }
        .mx-auto { margin-left: auto; margin-right: auto; }
        .my-10 { margin-top: 2.5rem; margin-bottom: 2.5rem; }
        .flex-col { flex-direction: column; }

        /* Text styles */
        .text-sm { font-size: 0.875rem; }
        .text-xl { font-size: 1.25rem; }
        .text-5xl { font-size: 3rem; }
        .font-bold { font-weight: 700; }
        .font-semibold { font-weight: 400; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }

        /* Colors */
        .text-blue-600 { color: #2563eb; }
        .text-gray-700 { color: #374151; }
        .text-green-500 { color: #10b981; }
        .text-red-500 { color: #ef4444; }
        .text-yellow-500 { color: #f59e0b; }
        .text-gray-500 { color: #6b7280; }
        .bg-gray-100 { background-color: #f3f4f6; }
        .bg-white { background-color: white; }
        .border-gray-200 { border-color: #e5e7eb; }

        /* Spacing */
        .px-10 { padding-left: 2.5rem; padding-right: 2.5rem; }
        .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .px-4 { padding-left: 1rem; padding-right: 1rem; }
        .py-2 { padding-top: 0.5rem; padding-bottom: 0.5rem; }
        .mb-4 { margin-bottom: 0.5rem; }
        .mb-5 { margin-bottom: 1rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mt-6 { margin-top: 1.5rem; }
        .my-6 { margin-top: 1.5rem; margin-bottom: 1.5rem; }

        /* Layout */
        .flex { display: flex; }
        .items-end { align-items: flex-end; }
        .justify-end { justify-content: flex-end; }
        .justify-center { justify-content: center; }
        .items-center { align-items: center; }
        .gap-2 { gap: 0.5rem; }

        /* Table styles */
        .min-w-full { min-width: 100%; }
        .border { border-width: 1px; border-style: solid; }
        .border-b { border-bottom-width: 1px; border-bottom-style: solid; }

        .balance {
            color: #f59e0b;
            font-weight: bold;
        }

        .sign {
            padding-right: 50;
            padding-left: 50;
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
        {{-- <div style="font-family:'Courier New', Courier, monospace; text-align: center; font-size: smaller;" >DOCUMENT GENERTED BY SHAKIR INVOICE SYSTEM {{ date('M d, Y h:i:A') }}</div> --}}
    </div>

    <div style="height: 40mm;"></div>

    <div class="invoice-content">
        <div class="invoice-meta">
            <div style="text-align: left;">
                <h1 class="mb-5" ><span class="meta-title">RECEIPT</span> #{{ $payment->reference_number }}</h1>
                @if($invoice->status == 'paid')
                    <p class="text-sm mb-4"><span class="meta-title">STATUS: </span><span class="{{ $invoice->status == 'paid' ? 'text-green-500' : ($invoice->status == 'overdue' ? 'text-red-500' : ($invoice->status == 'draft' ? 'text-gray-500' : 'text-yellow-500')) }} font-bold">{{ ucfirst($invoice->status) }}</span></p>
                @endif

                <p class="text-sm mb-4"><span class="meta-title">DATE: </span>{{ $payment->created_at->format('d-m-Y') }}</p>
                <p class="text-sm mb-4">
                    <span class="meta-title">PAYMENT METHOD: </span>
                    {{  (['bank_transfer'=>'Bank Transfer', 'mobile_money'=>'Mobile Money', 'cash'=>'Cash', 'cheque'=>'Cheque', 'other'=>'Other'][$payment->payment_method]) ?? 'N/A' }}
                </p>

                @if($invoice->is_recurring)
                    <p class="text-sm mb-4"><span class="meta-title">NEXT BILL: </span>{{ $invoice->next_recurring_date }}</p>
                @endif
            </div>
        </div>

        <div class="bill-to">
            <h2>RECEIVED FROM:</h2>
            <p>{{ $client->name }}</p>
            <p>{{ $client->address }}</p>
            <p>{{ $client->email }}</p>
            <p>{{ $client->phone }}</p>
        </div>

        <table>
            <thead>
                <span style="color: #f59e0b; font-weight: bold; margin-top: 1rem;">FOR:</span>
                <tr>
                    <th class="text-center">No.</th>
                    <th class="text-center">Item</th>
                    <th class="text-center">Qty</th>
                    <th class="text-center">Price (GHC)</th>
                    <th class="text-center">Total (GHC)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                    <tr>
                        <td class="text-center" style="width: 10%;">{{ $loop->iteration }}</td>
                        <td style="width: 40%;">{{ $item->product->name }}</td>
                        <td class="text-center" style="width: 10%;">{{ $item->quantity }}</td>
                        <td class="text-right">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">{{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-6 font-bold text-gray-700">INVOICE #<span>{{ $invoice->invoice_number }}</span></div>
        <div class="text-green-700" style="line-height: 40px;"><span class="text-yellow-500 font-bold">AMOUNT RECEIVED IN WORDS: </span>
            <span style="display: inline; border-bottom: 1px solid #f59e0b; padding-bottom: 2px;">
                {{ $amountInWords }}
            </span>
        </div>

        <table class="totals">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="value">GHC {{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Discount ({{ $invoice->discount_rate }}%):</td>
                <td class="value">-GHC {{ number_format($invoice->discount_amount, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Tax ({{ $invoice->tax_rate }}%):</td>
                <td class="value">GHC {{ number_format($invoice->tax, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Total:</td>
                <td class="value"><strong>GHC {{ number_format($invoice->total, 2) }}</strong></td>
            </tr>
{{--            @if($invoice->status == 'paid')--}}
                <tr>
                    <td class="label text-green">Amount Paid:</td>
                    <td class="value text-green">GHC {{ number_format($invoice->amount_paid, 2) }}</td>
                </tr>
                <tr>
                    <td class="label text-red">Balance Due:</td>
                    <td class="value text-red">GHC {{ number_format($invoice->balance, 2) }}</td>
                </tr>
{{--            @endif--}}
        </table>
        <div class="sign">
            <img src="{{ public_path('images/sign.png') }}" alt="Footer" style="width: 15%; height: auto;">
            <div style="width: 30%; border: 1px solid #2563eb;"></div>
            <div class="text-sm text-blue-600">Authorized Signatory</div>
        </div>
    </div>
</body>
</html>
