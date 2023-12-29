<x-mail::message>
    <x-slot name="imageUrl">https://productize.nyc3.cdn.digitaloceanspaces.com/productize/reset-password-mail-header.png</x-slot>
    <h1>Congratulations On Creating Your First Product!</h1>
    <p>Subject: {{$email_subject}}</p>
    <p>Message: {{$message}}</p>
    <p>Email: {{$email}}</p>
</x-mail::message>
