<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .invoice-details {
            margin-bottom: 30px;
        }

        .client-details {
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f5f5f5;
        }

        .totals {
            float: right;
            width: 300px;
        }

        .totals table {
            margin-bottom: 0;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>INVOICE</h1>
    <p>Invoice Number: {{ $invoice->invoice_number }}</p>
</div>

<div class="invoice-details">
    <p><strong>Issue Date:</strong> {{ $invoice->issue_date->format('M d, Y') }}</p>
    <p><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}</p>
    <p><strong>Status:</strong> {{ ucfirst($invoice->status) }}</p>
</div>

<div class="client-details">
    <h3>Bill To:</h3>
    <p><strong>{{ $client->company_name }}</strong></p>
    <p>{{ $client->address }}</p>
    <p>Phone: {{ $client->phone }}</p>
    <p>Email: {{ $client->email }}</p>
</div>

<table>
    <thead>
    <tr>
        <th>Description</th>
        <th>Quantity</th>
        <th>Unit Price</th>
        <th>Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach($items as $item)
        <tr>
            <td>{{ $item->description }}</td>
            <td>{{ $item->quantity }}</td>
            <td>GHS {{ number_format($item->unit_price, 2) }}</td>
            <td>GHS {{ number_format($item->total, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="totals">
    <table>
        <tr>
            <td><strong>Subtotal(GHS):</strong></td>
            <td style="text-align: right;"> {{ number_format($invoice->subtotal, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Tax Rate(%):</strong></td>
            <td style="text-align: right;">{{ number_format($invoice->tax_rate, 2) }} </td>
        </tr>
        <tr>
            <td><strong>Discount(%):</strong></td>
            <td style="text-align: right;">  {{ number_format($invoice->discount_rate, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Total(GHS):</strong></td>
            <td style="text-align: right;">{{ number_format($invoice->total, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Amount Paid(GHS):</strong></td>
            <td style="text-align: right;">{{ number_format($invoice->amount_paid, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Balance(GHS):</strong></td>
            <td style="text-align: right;">{{ number_format($invoice->total -$invoice->amount_paid, 2) }}</td>
        </tr>
    </table>
</div>

@if($invoice->notes)
    <div class="notes">
        <h3>Notes:</h3>
        <p>{{ $invoice->notes }}</p>
    </div>
@endif

@if($invoice->terms)
    <div class="terms">
        <h3>Terms & Conditions:</h3>
        <p>{{ $invoice->terms }}</p>
    </div>
@endif

<div class="footer">
    <p>Thank you for your business!</p>
</div>
</body>
</html>
