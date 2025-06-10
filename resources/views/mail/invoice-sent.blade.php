<x-mail::message>
# Dear {{ $invoice->client->name }},

We are pleased to inform you that your invoice #{{ $invoice->invoice_number }} has been successfully generated and is now
available for download.
<br>
You can view and download your invoice using this <a href="{{route('invoices.download', $invoice)}}">link here</a>.
<br>
Please <a href="{{route('payments.initialize', $invoice)}}"> make payment</a> by the due date to avoid any late fees.


Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
