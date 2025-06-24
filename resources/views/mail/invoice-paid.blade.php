<x-mail::message>
<img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }} Logo" style="height: 60px; margin-bottom: 20px;">

# Payment Received – Thank You!

Hello {{ $invoice->client->name }},

We’re pleased to confirm that we’ve received your payment of **GHC {{ number_format($amount, 2) }}** for Invoice **#{{ $invoice->invoice_number }}**.

<x-mail::panel>
**Payment Details**
Amount: GHC {{ number_format($amount, 2) }}
Invoice Number: {{ $invoice->invoice_number }}
Date: {{ now()->format('F j, Y') }}
Status: ✅ Paid
</x-mail::panel>

You can view or download your official invoice and receipt using the buttons below:

<x-mail::button :url="route('invoices.download', $invoice)">
📄 Download Invoice
</x-mail::button>

<x-mail::button :url="route('receipts.download', $invoice)">
🧾 View Receipt
</x-mail::button>

We appreciate your prompt payment and your continued trust in {{ config('app.name') }}.

Warm regards,
**The {{ config('app.name') }} Team**

<x-mail::subcopy>
If you're having trouble clicking the buttons, copy and paste the URLs below into your browser:
Invoice: {{ route('invoices.download', $invoice) }}
Receipt: {{ route('payments.receipt', $invoice) }}
</x-mail::subcopy>
</x-mail::message>
