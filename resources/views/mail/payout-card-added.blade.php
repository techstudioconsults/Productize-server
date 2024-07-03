<x-mail::layout>
    <div class="main-body">
        <h1>New Payout Card Added Successfully!</h1>

        <img style="max-width: 429px; height: 92px; margin-bottom: 40px;" src="https://productize.nyc3.cdn.digitaloceanspaces.com/emails/mastercard.png" alt="image">

        <x-mail::button :url="trim($url)" color="#6D5DD3">
            Change Settings
        </x-mail::button>
    </div>
</x-mail::layout>
