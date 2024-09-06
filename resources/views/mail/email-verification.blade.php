<x-mail::message>
    <x-slot name="imageUrl">https://productize.nyc3.cdn.digitaloceanspaces.com/productize/logo%20circle.png</x-slot>
    <h1>Hi {{ $name }}, Welcome To Bytealley.
        Verify Your Email Address.</h1>

    <p>Welcome to Bytealley, the ultimate digital marketplace for creators like you! We're thrilled to have you join our vibrant community and showcase your digital creations to the world.But before we embark on this exciting journey together, we need to verify your email address to ensure the security of your account and the authenticity of your creations.</p>

    <p>To confirm your email address and get started, please click on the verification button below:</p>

    <x-mail::button :url="trim($url)" color="success">
        Verify Email
    </x-mail::button>

    <p>By confirming your email, you'll gain full access to all the features and opportunities our platform offers, including selling your digital products, connecting with buyers, and becoming part of a creative community.</p>

    <p>Thank you for choosing Bytealley to share your talent and digital creations. We can't wait to see what you have in store for the world!</p>
</x-mail::message>
