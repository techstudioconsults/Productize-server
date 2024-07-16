<x-mail::message>
    <x-slot name="imageUrl">https://productize.nyc3.cdn.digitaloceanspaces.com/productize/reset-password-mail-header.png</x-slot>
    <h1>New Support Request Received</h1>
    <p>Hi Admin,</p>
    <p>A new support request has been submitted via the Contact Us form. Here are the details:</p>
    <p>First Name: {{$firstname}}</p>
    <p>Last Name: {{$lastname}}</p>
    <p>Subject: {{$subject}}</p>
    <p>Email: {{$email}}</p>
    <p>Message: {{$message}}</p> 
</x-mail::message>
