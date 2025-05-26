<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: 'Helvetica', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 40px;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }
        .invoice-header h1 {
            color: #2d3748;
            margin: 0;
            font-size: 32px;
        }
        .invoice-header h2 {
            color: #4a5568;
            margin: 5px 0 0;
            font-size: 24px;
        }
        .company-info {
            text-align: right;
        }
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        .client-info, .invoice-details {
            width: 45%;
        }
        .invoice-details table {
            width: 100%;
        }
        .invoice-details td {
            padding: 5px 0;
        }
        .invoice-items {
            margin-bottom: 40px;
        }
        .invoice-items table {
            width: 100%;
            border-collapse: collapse;
        }
        .invoice-items th {
            background-color: #f7fafc;
            text-align: left;
            padding: 12px;
            border-bottom: 2px solid #e2e8f0;
        }
        .invoice-items td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .invoice-totals {
            width: 300px;
            margin-left: auto;
        }
        .invoice-totals table {
            width: 100%;
        }
        .invoice-totals td {
            padding: 8px 0;
        }
        .invoice-totals .total-row {
            font-weight: bold;
            border-top: 2px solid #e2e8f0;
            font-size: 18px;
        }
        .invoice-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #eee;
            font-size: 14px;
            color: #718096;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-draft { background-color: #e2e8f0; color: #4a5568; }
        .status-sent { background-color: #ebf8ff; color: #2b6cb0; }
        .status-paid { background-color: #f0fff4; color: #2f855a; }
        .status-overdue { background-color: #fff5f5; color: #c53030; }
        .status-partial { background-color: #faf5ff; color: #6b46c1; }
    </style>
</head>
<body>
    <div class="invoice-header">
        <div>
            <h1>INVOICE</h1>
            <h2>{{ $invoice->invoice_number }}</h2>
        </div>
        <div class="company-info">
            <strong>Your Company Name</strong><br>
            123 Business Street<br>
            City, State 12345<br>
            contact@yourcompany.com
        </div>
    </div>

    <div class="invoice-info">
        <div class="client-info">
            <strong>Bill To:</strong><br>
            {{ $invoice->client->name }}<br>
            @if($invoice->client->company_name)
                {{ $invoice->client->company_name }}<br>
            @endif
            {{ $invoice->client->address }}<br>
            {{ $invoice->client->city }}, {{ $invoice->client->state }} {{ $invoice->client->postal_code }}<br>
            {{ $invoice->client->email }}
        </div>
        <div class="invoice-details">
            <table>
                <tr>
                    <td><strong>Issue Date:</strong></td>
                    <td>{{ $invoice->issue_date->format('F j, Y') }}</td>
                </tr>
                <tr>
                    <td><strong>Due Date:</strong></td>
                    <td>{{ $invoice->due_date->format('F j, Y') }}</td>
                </tr>
                <tr>
                    <td><strong>Status:</strong></td>
                    <td>
                        <span class="status-badge status-{{ $invoice->status }}">
                            {{ ucfirst($invoice->status) }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="invoice-items">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Unit Price (GHS)</th>
                    <th>Tax (GHS)</th>
                    <th>Discount (GHS)</th>
                    <th>Total (GHS)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $item->product->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price, 2, '.', ',') }}</td>
                    <td>{{ number_format($item->tax_amount, 2, '.', ',') }}</td>
                    <td>{{ number_format($item->discount_amount, 2, '.', ',') }}</td>
                    <td>{{ number_format($item->total, 2, '.', ',') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="invoice-totals">
        <table>
            <tr>
                <td>Subtotal (GHS):</td>
                <td align="right">{{ number_format($invoice->subtotal, 2, '.', ',') }}</td>
            </tr>
            @if($invoice->tax_amount > 0)
            <tr>
                <td>Tax ({{ $invoice->tax_rate }}%):</td>
                <td align="right">{{ number_format($invoice->tax_amount, 2, '.', ',') }}</td>
            </tr>
            @endif
            @if($invoice->discount_amount > 0)
            <tr>
                <td>Discount ({{ $invoice->discount_rate }}%):</td>
                <td align="right">-{{ number_format($invoice->discount_amount, 2, '.', ',') }}</td>
            </tr>
            @endif
            <tr class="total-row">
                <td>Total:</td>
                <td align="right">{{ number_format($invoice->grand_total, 2, '.', ',') }}</td>
            </tr>
            @if($invoice->amount_paid > 0)
            <tr>
                <td>Amount Paid:</td>
                <td align="right">{{ number_format($invoice->amount_paid, 2, '.', ',') }}</td>
            </tr>
            <tr>
                <td>Balance Due:</td>
                <td align="right">{{ number_format($invoice->balance, 2, '.', ',') }}</td>
            </tr>
            @endif
        </table>
    </div>

    @if($invoice->notes || $invoice->terms)
    <div class="invoice-footer">
        @if($invoice->notes)
        <div style="margin-bottom: 20px;">
            <strong>Notes:</strong><br>
            {{ $invoice->notes }}
        </div>
        @endif
        @if($invoice->terms)
        <div>
            <strong>Terms & Conditions:</strong><br>
            {{ $invoice->terms }}
        </div>
        @endif
    </div>
    @endif
</body>
</html>
