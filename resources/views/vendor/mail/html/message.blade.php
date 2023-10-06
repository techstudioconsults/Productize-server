<x-mail::layout>
    <div class="main-logo">
        <img src="{{ $imageUrl }}" alt="circle">
    </div>
    <div class="main-body">
        {{ $slot }}
    </div>
</x-mail::layout>
