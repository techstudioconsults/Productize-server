<x-mail::message>
    <x-slot name="imageUrl">https://productize.nyc3.cdn.digitaloceanspaces.com/productize/reset-password-mail-header.png</x-slot>

    <h1>You have successfully Purchased a Product</h1>

    <p>
        We're excited to inform you that your purchase of ({{ $title }}) was successful.
    </p>

    <p>
        You now have access to your new product, and we hope it meets your expectations.
    </p>

    <x-mail::button :url="trim($url)" color="#6D5DD3">
        View Now
    </x-mail::button>
</x-mail::message>
