<x-mail::message>
    <x-slot name="imageUrl">https://productize.nyc3.cdn.digitaloceanspaces.com/productize/reset-password-mail-header.png</x-slot>

    <h1>New product published successfully!</h1>

    <img style="max-width: 501px; height: 186px;" src="{{ $thumbnail }}" alt="image">

    <p>
        Great news! Your digital creation is now live on productize. Congratulations on publishing your product for the world to see. This is just the beginning of your journey with us, and we're excited to see your creation reach new heights. Feel free to share your product with your audience and explore the opportunities that lie ahead.
    </p>

    <p>
        If you have any questions or need assistance, our team is here to support you. Let's make your digital dreams a reality!
    </p>

    <div class="link">
        <div>
            {{ $link }}
        </div>
    </div>
</x-mail::message>
