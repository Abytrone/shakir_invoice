<x-mail::message>
# Dear {{ $invoice->client->name }},

Your invoice #{{ $invoice->invoice_number }} is due in {{$daysBeforeDueDate}} days.
<br>
Please make payment on time to avoid disconnection of service.
<br>
You can view and download your invoice using this <a href="{{$invoiceDownloadUrl}}">link here</a>.
<br>
Please <a href="{{$invoicePaymentInitUrl}}"> make payment</a> before the due date to avoid any late fees.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
