<x-mail::message>
    <x-slot name="imageUrl">https://productize.nyc3.cdn.digitaloceanspaces.com/productize/reset-password-mail-header.png</x-slot>
    <h1>Admin Account Details Update</h1>
    <p>Hi Admin {{$fullname}},</p>
    <p>We wanted to let you know that your admin account details on Bytealley have been successfully updated.</p>
    <p>Updated Name: {{$fullname}}</p> 
    <p>Updated Password: {{$password}}</p> 

   <x-mail::button :url="url('https://tsa-productize.vercel.app/')" color="success">
    Log in to Admin Account
   </x-mail::button>

</x-mail::message>
