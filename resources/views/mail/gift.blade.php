<x-mail::message>
    <x-slot name="imageUrl">https://productize.nyc3.cdn.digitaloceanspaces.com/productize/reset-password-mail-header.png</x-slot>

    <h1>Congratulations, a product has been gifted to you!</h1>

    <p>
        We are excited to inform you that {{ $buyer_email }} has gifted you a digital product on Bytealley! To access your gifted course and start learning, please login.
    </p>

    <x-mail::button :url="trim($url)" color="#6D5DD3">
        Login
    </x-mail::button>

    <p>
        We hope you enjoy your new product and make the most of it!. If you have any further questions or concerns, please don't hesitate to reach out to us.
    </p>

</x-mail::message>
