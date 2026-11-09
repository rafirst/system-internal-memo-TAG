@props(['status'])

<span {{ $attributes->class(['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', $status->color()]) }}>
    {{ $status->label() }}
</span>
