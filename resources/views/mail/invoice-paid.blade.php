<x-mail::message>
# Dear {{ $invoice->client->name }},

Payment of GHC {{$amount}} has been received for invoice #{{ $invoice->invoice_number }}.
<br>
You can view and download your invoice using this <a href="{{route('invoices.download', $invoice)}}">link here</a>.
<br>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
