<x-mail::message>
    <div style="text-align: center; margin-bottom: 20px;">
        <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" style="height: 60px;">
    </div>

    # Hello {{ $invoice->client->name }},

    @if($invoice->is_recurring)
        We appreciate your continued business with {{ config('app.name') }}. Please find below the billing details for the
        services provided to you for the **month of {{ $invoice->issue_date->format('F') }}**.
    @else
        We’re pleased to let you know your invoice **#{{ $invoice->invoice_number }}** is ready.
    @endif

    <x-mail::panel>
        **Amount Due:** GH₵ {{ number_format($invoice->total, 2) }}
        **Due Date:** {{ \Carbon\Carbon::parse($invoice->due_date)->format('F j, Y') }}
    </x-mail::panel>

    <x-mail::button color="primary" :url="$invoiceDownloadUrl">
        📄 View Invoice
    </x-mail::button>

    Thank you for trusting {{ config('app.name') }}.
    We look forward to continuing our service with you.

    Warm regards,
    **The {{ config('app.name') }} Team**

    <x-mail::subcopy>
        If you're having trouble clicking the button, use this link:
        {{ $invoiceDownloadUrl }}
    </x-mail::subcopy>
</x-mail::message>