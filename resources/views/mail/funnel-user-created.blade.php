<x-mail::message>
    <h1>Your account has been created!</h1>

    <x-slot name="imageUrl">https://productize.nyc3.cdn.digitaloceanspaces.com/productize/reset-password-mail-header.png</x-slot>

    <p>
        We've created an account for you so you can access your package anytime. Here are your login details:
    </p>

    <p> <b>Email:</b> {{$email}}</p>
    <p> <b>Password:</b> {{$password}}</p>

    <x-mail::button :url="trim($url)" color="#6D5DD3">
        Sign in to your Account
    </x-mail::button>

    <p>
        Once logged in, you can change your password and access your package in the Downloads tab. Need help? Our support team is here for you.
    </p>
</x-mail::message>
