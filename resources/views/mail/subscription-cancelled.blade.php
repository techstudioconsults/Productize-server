<x-mail::message>
    <x-slot name="imageUrl">https://productize.nyc3.cdn.digitaloceanspaces.com/emails/subscription-cancel.png</x-slot>

    <h1>Your Subscription Has Been Cancelled</h1>

    <p>
        We hope you enjoyed exploring <b>PRODUCTIZE</b> during your subscription. As of today, your subscription has been canceled.
    </p>

    <p>
        We trust you've had a taste of the value and benefits our product offers. If you ever decide to come back, we'll be here to welcome you with open arms.
    </p>

    <x-mail::button :url="trim($url)" color="#6D5DD3">
        Subscribe Now
    </x-mail::button>
</x-mail::message>
