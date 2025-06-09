@if(!$invoice)
    payment error
@endif

@if($invoice)
    payment success
@endif
{{$message}}
