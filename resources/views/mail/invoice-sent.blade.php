<x-mail::message>
# Introduction

The body of your message.

<x-mail::button :url="route('invoices.print', $invoice)">
Download Invoice
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
