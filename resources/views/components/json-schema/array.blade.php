@props([
    'name' => 'root',
    'statePath' => '.',
    'items' => [
        'type' => 'object',
        'properties' => [],
        'required' => [],
    ],
])

<x-llm-magic::json-schema.loop :$name :$statePath>
    <x-llm-magic::json-schema.object :schema="$items" />
</x-llm-magic::json-schema.loop>
