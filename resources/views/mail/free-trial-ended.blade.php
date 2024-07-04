<x-mail::message>
    <x-slot name="imageUrl">https://productize.nyc3.cdn.digitaloceanspaces.com/emails/cry.png</x-slot>

    <h1>Your Free Trial Has Ended</h1>

    <p>
        We hope you enjoyed exploring <b>PRODUCTIZE</b> during your free trial. As of today, your trial period has come to an end.
    </p>

    <p>
        We trust you've had a taste of the value and benefits our product offers. To continue enjoying uninterrupted access to all premium features, we invite you to subscribe now.
    </p>

    <x-mail::button :url="trim($url)" color="#6D5DD3">
        Subscribe Now
    </x-mail::button>
</x-mail::message>
