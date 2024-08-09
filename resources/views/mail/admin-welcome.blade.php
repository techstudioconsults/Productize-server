<x-mail::message>
    <x-slot name="imageUrl">https://productize.nyc3.cdn.digitaloceanspaces.com/productize/reset-password-mail-header.png</x-slot>
    <h1>Welcome to the Productize Admin Team!</h1>
    <p>We are excited to inform you that you have been added as an admin on Productize. Below are your login details:</p>
    <p>Admin Email: {{$email}}</p>
    <p>Password: {{$password}}</p>  
  <x-mail::button :url="url('https://tsa-productize.vercel.app/')" color="success">
     Log in to Admin Account
  </x-mail::button>
</x-mail::message>
