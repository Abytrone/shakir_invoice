<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill for {{ \Carbon\Carbon::parse($invoice->issue_date)->format('F Y') }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
            font-family: 'Urbanist', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        .wrapper {
            width: 100%;
            background-color: #f3f4f6;
            padding: 40px 20px;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            padding: 40px;
            text-align: center;
        }

        /* Different gradient for recurring */
        .logo {
            max-width: 200px;
            height: auto;
            margin-bottom: 20px;
            background-color: rgba(255, 255, 255, 1);
            padding: 10px;
            border-radius: 8px;
            backdrop-filter: blur(5px);
        }

        .invoice-badge {
            display: inline-block;
            background-color: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            padding: 6px 16px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 500;
            margin-top: 10px;
        }

        .content {
            padding: 40px;
        }

        .greeting {
            font-size: 24px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 16px;
        }

        .message {
            font-size: 16px;
            line-height: 1.6;
            color: #4b5563;
            margin-bottom: 32px;
        }

        .details-card {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 32px;
        }

        .month-label {
            font-size: 13px;
            font-weight: 600;
            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .amount-label {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .amount {
            font-size: 36px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 16px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #64748b;
            font-size: 15px;
        }

        .detail-value {
            color: #334155;
            font-weight: 600;
            font-size: 15px;
        }

        .action-button {
            display: block;
            width: 100%;
            background-color: #2563eb;
            color: #ffffff;
            text-align: center;
            padding: 16px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            margin-bottom: 32px;
            transition: background-color 0.2s;
        }

        .action-button:hover {
            background-color: #2563eb;
        }

        .footer {
            background-color: #f8fafc;
            padding: 24px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .footer-text {
            font-size: 14px;
            color: #94a3b8;
            margin-bottom: 8px;
        }

        /* Responsive Styles */
        @media only screen and (max-width: 600px) {
            .wrapper {
                padding: 10px !important;
            }

            .container {
                width: 100% !important;
                border-radius: 8px !important;
            }

            .header {
                padding: 24px !important;
            }

            .content {
                padding: 24px !important;
            }

            .greeting {
                font-size: 20px !important;
            }

            .amount {
                font-size: 28px !important;
            }

            .action-button {
                padding: 14px !important;
                font-size: 15px !important;
            }
        }
    </style>
</head>

<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: 'Urbanist', sans-serif;">
    <div class="wrapper" style="width: 100%; background-color: #f3f4f6; padding: 40px 20px; box-sizing: border-box;">
        <div class="container" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);">
            <div class="header" style="background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); padding: 40px; text-align: center;">
                {{-- Setup logo. --}}
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="logo" style="max-width: 200px; height: auto; margin-bottom: 20px; background-color: rgba(255, 255, 255, 1); padding: 10px; border-radius: 8px; backdrop-filter: blur(5px);">
                <div style="color: white; font-size: 18px; font-weight: 600; font-family: 'Urbanist', sans-serif;">{{ config('app.name') }}</div>
                <div class="invoice-badge" style="display: inline-block; background-color: rgba(255, 255, 255, 0.2); color: #ffffff; padding: 6px 16px; border-radius: 50px; font-size: 14px; font-weight: 500; margin-top: 10px; font-family: 'Urbanist', sans-serif;">Monthly Bill</div>
            </div>

            <div class="content" style="padding: 40px;">
                <h1 class="greeting" style="font-size: 24px; font-weight: 600; color: #111827; margin-bottom: 16px; margin-top: 0; font-family: 'Urbanist', sans-serif;">Hello, {{ $invoice->client->name }}</h1>
                <p class="message" style="font-size: 16px; line-height: 1.6; color: #4b5563; margin-bottom: 32px; font-family: 'Urbanist', sans-serif;">
                    We hope you’re having a great month. This is your recurring bill for the month of
                    <strong>{{ \Carbon\Carbon::parse($invoice->issue_date)->format('F') }}</strong>.
                </p>

                <div class="details-card" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 32px;">
                    <div class="month-label" style="font-size: 13px; font-weight: 600; color: #2563eb; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; font-family: 'Urbanist', sans-serif;">Billing Period: {{ \Carbon\Carbon::parse($invoice->issue_date)->format('F Y') }}</div>
                    <div class="amount-label" style="font-size: 14px; color: #64748b; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px; font-family: 'Urbanist', sans-serif;">Amount Due</div>
                    <div class="amount" style="font-size: 36px; font-weight: 700; color: #0f172a; margin-bottom: 16px; font-family: 'Urbanist', sans-serif;">GH₵ {{ number_format($invoice->total, 2) }}</div>

                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="detail-label" style="padding: 12px 0; border-bottom: 1px solid #e2e8f0; color: #64748b; font-size: 15px; font-family: 'Urbanist', sans-serif;">Invoice Number</td>
                            <td class="detail-value"
                                style="padding: 12px 0; border-bottom: 1px solid #e2e8f0; text-align: right; color: #334155; font-weight: 600; font-size: 15px; font-family: 'Urbanist', sans-serif;">
                                #{{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <td class="detail-label" style="padding: 12px 0; color: #64748b; font-size: 15px; font-family: 'Urbanist', sans-serif;">Due Date</td>
                            <td class="detail-value" style="padding: 12px 0; text-align: right; color: #334155; font-weight: 600; font-size: 15px; font-family: 'Urbanist', sans-serif;">
                                {{ \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') }}
                            </td>
                        </tr>
                    </table>
                </div>

                <a href="{{ $invoiceDownloadUrl }}" class="action-button" style="display: block; width: 100%; background-color: #2563eb; color: #ffffff; text-align: center; padding: 16px; border-radius: 10px; font-weight: 600; font-size: 16px; text-decoration: none; margin-bottom: 32px; box-sizing: border-box; transition: background-color 0.2s; font-family: 'Urbanist', sans-serif;">
                    View Bill Details
                </a>

                <p style="font-size: 14px; color: #64748b; text-align: center;">
                    If you have any questions about this bill, please contact our support team.<br>
                    <a href="{{ $invoiceDownloadUrl }}"
                        style="color: #2563eb; word-break: break-all; margin-top: 10px; display: inline-block;">{{ $invoiceDownloadUrl }}</a>
                </p>
            </div>

            <div class="footer" style="background-color: #f8fafc; padding: 24px; text-align: center; border-top: 1px solid #e2e8f0;">
                <p class="footer-text" style="font-size: 14px; color: #94a3b8; margin-bottom: 8px; margin-top: 0; font-family: 'Urbanist', sans-serif;">© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                <p class="footer-text" style="font-size: 14px; color: #94a3b8; margin-bottom: 0; font-family: 'Urbanist', sans-serif;">Thank you for your continued business!</p>
            </div>
        </div>
    </div>
</body>

</html>
