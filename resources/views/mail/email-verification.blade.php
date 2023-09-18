<x-mail::message>
    <x-slot name="imageUrl">https://productize.nyc3.cdn.digitaloceanspaces.com/productize/logo%20circle.png</x-slot>
    <h1>Hi {{ $name }}, Welcome To Productize.
        Verify Your Email Address.</h1>

    <p>Lorem ipsum dolor sit amet consectetur. Habitant aliquet suscipit sed facilisi sit. Nibh at nisl augue viverra vitae amet orci lorem. Luctus faucibus laoreet eu parturient in. Elementum consectetur enim fames velit sit donec.</p>

    <x-mail::button :url="trim($url)" color="success">
        Verify Email
    </x-mail::button>
</x-mail::message>
