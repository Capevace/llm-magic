@props([
    /** @var string $statePath */
    'statePath',

    /** @var string $name */
    'name',

    'sortedByJs' => true,

    'tag' => 'div'
])

@if ($tag !== null)
<{{ $tag }}
    {{ $attributes }}
    x-data="{
        hashObject(obj) {
            const jsonString = JSON.stringify(obj); // Convert the object to a string representation
            let hash = 0;

            // Simple string hash algorithm (djb2)
            for (let i = 0; i < jsonString.length; i++) {
                const char = jsonString.charCodeAt(i);
                hash = ((hash << 5) - hash) + char; // hash * 31 + char
                hash |= 0; // Convert to 32bit integer
            }

            return Math.abs(hash); // Return positive hash for convenience
        },
        {{ $name }}_sorted() {
            const data = Object.values(this.{{ $statePath }} ?? {});

            if (!this.sort) {
                return Object.values(data)
                    .map((value, index) => ({ {{ $name }}: value, {{ $name }}_index: index }));
            }

            return Object.values(data)
                .map((value, index) => ({ {{ $name }}: value, {{ $name }}_index: index }))
                .sort((a, b) => {
                    if (this.sortDirection === 'asc') {
                        return a.{{ $name }}[this.sort] < b.{{ $name }}[this.sort] ? 1 : -1;
                    }
                    return a.{{ $name }}[this.sort] > b.{{ $name }}[this.sort] ? 1 : -1;
                })
                .filter((value) => value.{{ $name }}[this.sort] !== null);
        }
    }"
>
@endif
    <template
        x-for="({ {{ $name }}, {{ $name }}_index }) in {{ $name }}_sorted()"
        :key="{{ $name }}_index"
    >
        {{ $slot }}
    </template>
@if ($tag !== null)
</{{ $tag }}>
@endif
