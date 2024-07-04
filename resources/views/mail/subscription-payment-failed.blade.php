<x-mail::message>
    <x-slot name="imageUrl">https://productize.nyc3.cdn.digitaloceanspaces.com/emails/subscription-cancel.png</x-slot>

    <h1>Your Subscription Payment Has Failed</h1>

    <p>
        We're sorry to inform you that we couldn't process your payment for your <b>PRODUCTIZE</b> subscription. Your access to premium features may be temporarily disrupted.
    </p>

    <p>

        Please update your payment information in your account to avoid any interruptions in service.
        If you need help, contact us.
    </p>

    <h6 class="text-left">Reason: {{ $reason }}</h6>

    <x-mail::button :url="trim($url)" color="#6D5DD3">
        Contact Us
    </x-mail::button>
</x-mail::message>
