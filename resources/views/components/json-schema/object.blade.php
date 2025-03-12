@props([
    'name' => 'root',
    'statePath' => '.',
    'schema' => [
        'type' => 'object',
        'properties' => [],
        'required' => [],
    ],
])

<?php
$properties = $schema['properties'] ?? [];
$required = collect($schema['required'] ?? []);
?>


@foreach($properties as $property => $propertySchema)

@endforeach

@if ($schema['type'] === 'object')

@elseif ($schema['type'] === 'array')

@else
    <x-llm-magic::json-schema.property :name="$name" :schema="$schema" />
@endif

<any name="root" state-path="." :schema="['type' => 'object', 'properties' => ['test' => ['type' => 'string']]]">
    <x-llm-magic::json-schema.assign name="root_test" state-path="root?.test">
        <input x-model="root_test" />
    </x-llm-magic::json-schema.assign>
</any>


<any name="root" state-path="." :schema="['type' => 'object', 'properties' => ['tests' => ['type' => 'array', 'items' => ['type' => 'string']]]]">
    <x-llm-magic::json-schema.assign name="root_tests" state-path="root?.tests">
        <x-llm-magic::json-schema.loop name="root_tests" state-path="root?.tests">
            <input x-model="root_tests.0" />
            <input x-model="root_tests.1" />
            <input x-model="root_tests.2" />
        </x-llm-magic::json-schema.loop>
    </x-llm-magic::json-schema.assign>
</any>
