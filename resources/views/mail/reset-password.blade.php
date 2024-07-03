<x-mail::message>
    <x-slot name="imageUrl">https://productize.nyc3.cdn.digitaloceanspaces.com/productize/reset-password-mail-header.png</x-slot>

    <h1>Reset Password</h1>

    <p>
        Please click on the button below for your password change. If you initiated this change, please proceed with the change using the button below.
    </p>

    <small>This password reset link will expire in {{ $count }} minutes. If you did not request a password reset, no further action is required.</small>

    <x-mail::button :url="trim($url)" color="#6D5DD3">
        Reset Password
    </x-mail::button>

    <p>
        However, if you did not request this password reset, please contact our support team immediately at [Support Email Address]. Your account's security is our top priority, and we'll assist you in taking any necessary steps to ensure its safety.
    </p>

    <p>
        Thank you for being a part of Productize. If you have any further questions or concerns, please don't hesitate to reach out to us.
    </p>

    <p class="text-left">
        Best regards,
        <br>
        The Productize Team"
    </p>
</x-mail::message>
