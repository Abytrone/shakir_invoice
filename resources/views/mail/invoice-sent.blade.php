<x-mail::message>
<img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }} Logo" style="height: 60px; margin-bottom: 20px;">

# Hello {{ $invoice->client->name }},

We’re pleased to let you know your invoice **#{{ $invoice->invoice_number }}** is ready.

<x-mail::panel>
**Amount Due:** GH₵ {{ number_format($invoice->total, 2) }}
**Due Date:** {{ \Carbon\Carbon::parse($invoice->due_date)->format('F j, Y') }}
</x-mail::panel>

<x-mail::button color="primary" :url="route('invoices.print', $invoice)">
📄 View Invoice
</x-mail::button>

<x-mail::button color="success" :url="route('payments.initialize', $invoice)">
💳 Make Payment
</x-mail::button>

Thank you for trusting {{ config('app.name') }}.
We look forward to continuing our service with you.

Warm regards,
**The {{ config('app.name') }} Team**

<x-mail::subcopy>
If you're having trouble clicking the buttons, use these links:
Invoice: {{ route('invoices.print', $invoice) }}
Payment: {{ route('payments.initialize', $invoice) }}
</x-mail::subcopy>
</x-mail::message>
