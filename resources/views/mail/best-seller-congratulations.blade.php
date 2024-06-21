<x-mail::message>
    <x-slot name="imageUrl">https://productize.nyc3.cdn.digitaloceanspaces.com/emails/numer_one_email.png</x-slot>
    <h1>You're #1! Congratulations on becoming our Top Selling Product!</h1>
    <img style="max-width: 501px; height: 186px;" src="{{ $thumbnail }}" alt="image">
    <p>
        Congratulations! Your product <b>{{ $title }}</b> have secured {{$position}} postion among our best-selling items, ranking within the top 5 on our platform! This achievement is a testament to your hard work and expertise. Keep up the fantastic work, and we're confident that you'll continue to excel."
    </p>
</x-mail::message>
