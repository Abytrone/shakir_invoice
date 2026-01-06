<x-mail::message>
<img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }} Logo" style="height: 60px; margin-bottom: 20px;">

# Hello {{ $client->name }},

Please click on the link below to authorize your card for automatic billing.
The amount to be authorized is {{ $amount }} but subsequently, only the actual invoice amount will be charged.


<x-mail::button color="primary" :url="$url">
    📄 Authorize
</x-mail::button>


Thank you for trusting {{ config('app.name') }}.
We look forward to continuing our service with you.

Warm regards,
**The {{ config('app.name') }} Team**

<x-mail::subcopy>
    If you're having trouble clicking the buttons, use these links:
    Authorization Url: {{ $url }}
</x-mail::subcopy>
</x-mail::message>
