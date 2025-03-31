<x-mail::message>
    <x-slot name="imageUrl">https://productize.nyc3.cdn.digitaloceanspaces.com/productize/reset-password-mail-header.png</x-slot>

    <h1>Your Package is Here!</h1>

    <img style="max-width: 429px; height: 92px; margin-bottom: 40px;" src="{{$thumbnail}}" alt="image">

    <p>
        Thank you for your interest in <b>"{{$title}}"!</b> 🎉
    </p>

    <p>We're thrilled to have you onboard and are confident you'll love it.</p>

    <p> Click the link below to access your package:</p>

    <x-mail::naked-button :url="trim($url)">
        {{$button}}
    </x-mail::naked-button>

    <p>
        <b>
            Please note that this freebie file below is separate from your main package and is designed to give you a head start!</b>
    </p>

    <p>if you have any questions or need further assistance, feel free to reach out to our support team.</p>
</x-mail::message>
