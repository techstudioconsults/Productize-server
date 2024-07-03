<x-mail::message>
    <x-slot name="imageUrl">https://productize.nyc3.cdn.digitaloceanspaces.com/emails/sad.png</x-slot>

    <h1>Important Reminder!! 3 Days To left In Your Free Trial</h1>

    <p>
        We hope you're enjoying your experience with PRODUCTIZE during your free trial! This is a friendly reminder that you have just 3 days remaining before your trial period concludes. We want to ensure you have ample time to explore all the features and benefits available to you.
    </p>

    <p>
        If you have any questions or need assistance, our support team is here to help. We hope you choose to continue your journey with us beyond the trial.
    </p>

    <x-mail::button :url="trim($url)" color="#6D5DD3">
        Subscribe Now
    </x-mail::button>
</x-mail::message>
