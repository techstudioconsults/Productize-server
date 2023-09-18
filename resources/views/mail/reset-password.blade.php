<x-mail::message>
    <x-slot name="imageUrl">https://productize.nyc3.cdn.digitaloceanspaces.com/productize/reset-password-mail-header.png</x-slot>

    <h1>Reset Password</h1>

    <p>You are receiving this email because we received a password reset request for your account.</p>

    <small>This password reset link will expire in {{ $count }} minutes. If you did not request a password reset, no further action is required.</small>

    <x-mail::button :url="trim($url)" color="#6D5DD3">
        Reset Password
    </x-mail::button>
</x-mail::message>
