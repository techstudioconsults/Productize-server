@props([
    'url',
    'align' => 'center',
])
<a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="naked-button">
    {{ $slot }}
</a>
