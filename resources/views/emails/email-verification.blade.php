<x-mail::message>
# Hi {{$name}},

<p>Thank you for registering with us.</p>
<small>click on the button below to verify your email address</small>

<x-mail::button :url="$url" color="success">
Verify Email Address
</x-mail::button>

Thanks,<br>
Tobi Olanitori, <br>
{{ config('app.name') }}
</x-mail::message>

