<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .content {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Invoice {{ $invoice->invoice_number }}</h1>
        </div>

        <div class="content">
            <p>Dear {{ $invoice->client->name }},</p>

            <p>Please find attached the invoice {{ $invoice->invoice_number }} for your recent purchase.</p>

            <p><strong>Invoice Details:</strong></p>
            <ul>
                <li>Invoice Number: {{ $invoice->invoice_number }}</li>
                <li>Issue Date: {{ $invoice->issue_date->format('F j, Y') }}</li>
                <li>Due Date: {{ $invoice->due_date->format('F j, Y') }}</li>
                <li>Total Amount: ${{ number_format($invoice->total, 2) }}</li>
            </ul>

            <p>You can download the invoice by clicking the button below or find it attached to this email.</p>

            <p style="text-align: center;">
                <a href="{{ \Illuminate\Support\Facades\URL::signedRoute('invoices.download', $invoice) }}" class="button">Download Invoice</a>
            </p>

            <p>If you have any questions about this invoice, please don't hesitate to contact us.</p>

            <p>Thank you for your business!</p>
        </div>

        <div class="footer">
            <p>This is an automated message, please do not reply directly to this email.</p>
            <p>&copy; {{ date('Y') }} Your Company Name. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
