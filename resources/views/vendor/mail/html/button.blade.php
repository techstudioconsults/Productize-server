@props([
    'url',
    'color',
    'align' => 'center',
])
<a href="{{ $url }}" target="_blank" rel="noopener noreferrer" class="button">
    {{ $slot }}
</a>
