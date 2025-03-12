@props([
    'color' => 'gray',
    'label' => 'Daten',
	'property',
    'name',
    'statePath',
    'schema' => [
        'type' => 'string',
        'enum' => ['test'],
    ],
    'required' => false,
])
<div
    type="button"
    @class([
        'cursor-pointer flex items-center gap-2 justify-between',
    ])

    @click="
        if (sort === '{{ $property }}' && sortDirection === 'asc') {
            sortDirection = 'desc';
        } else if (sort === '{{ $property }}' && sortDirection === 'desc') {
            sort = null;
            sortDirection = 'asc';
        } else if (sort !== '{{ $property }}') {
            sort = '{{ $property }}';
            sortDirection = 'asc';
        }
    "
>
    <label class="inline-flex items-center gap-x-3">
        <span class="text-xs font-medium leading-6 text-gray-950 dark:text-white">
            {{ $label }}
            @if ($required)
                <sup class="text-danger-600 dark:text-danger-400 font-bold">*</sup>
            @endif
        </span>
    </label>

    <nav x-show="sort === '{{ $property }}'" class="flex-shrink-0 flex items-center gap-2">
        <x-filament::icon-button
            icon="heroicon-o-bars-arrow-down"
            color="danger"
            size="xs"
            x-show="sortDirection === 'asc'"
        />

        <x-filament::icon-button
            icon="heroicon-o-bars-arrow-up"
            color="danger"
            size="xs"
            x-show="sortDirection === 'desc'"
        />
    </nav>
</div>
