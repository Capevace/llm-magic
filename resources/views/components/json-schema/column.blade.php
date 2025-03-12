@props([
    'color' => 'gray',
    'label' => 'Daten',
    'column',
    'name',
    'statePath',
    'rootStatePath',
    'validationRootStatePath',
    'schema' => [
        'type' => 'string',
        'enum' => ['test'],
    ],
    'required' => false,
    'disabled' => false,
])

<?php
  $matchesTypes = function (array|string $type, array $typesToMatch) {
      if (is_array($type)) {
          $type = $type[0];
      }

      return in_array($type, $typesToMatch);
  };


  $validationPath = str($statePath)
    ->replace($rootStatePath, $validationRootStatePath)
    ->toString();
?>

<div
    {{ $attributes->class('h-full w-auto') }}
    x-data="{
        get error() {
            try {
                return this.{!! $validationPath !!};
            } catch (e) {
                return false;
            }
        },
    }"
    :style="error
        ? 'background-color: rgba(var(--danger-500), 0.2);'
        : ''
    "
    x-tooltip="error"
>
    @if ($schema['type'] === 'array')
        <div class="text-xs text-warning-500 dark:text-warning-400">nested arrays not supported</div>
    @elseif ($matchesTypes($schema['type'], ['object']))
        <div class="text-xs text-warning-500 dark:text-warning-400">nested objects not supported</div>
    @elseif ($matchesTypes($schema['type'], ['integer', 'number', 'float', 'string']) && ($schema['format'] ?? null) === 'artifact-id')
        <x-llm-magic::json-schema.artifact-image
            :run-id="$this->runId"
            :state-path="$statePath"
            limit-width
        />
    @elseif ($matchesTypes($schema['type'], ['integer', 'number', 'float', 'string']))
        <x-filament::input
            x-data="{ focused: false }"
            @focus="{{ $column }}_focused = true; focused = true"
            @blur="{{ $column }}_focused = false; focused = false"
            class="!py-0 h-full !pl-2 !pr-2 !text-xs min-w-32"
            x-bind:class="{
                'shadow-inner': focused,
            }"
            x-model="{{ $statePath }}"
            :$disabled
            :$required
            :type="match (true) {
                $matchesTypes($schema['type'], ['integer', 'number', 'float']) => 'number',
                $matchesTypes($schema['type'], ['string']) => 'text',
                default => 'text',
            }"
            :minlength="match (true) {
                $matchesTypes($schema['type'], ['string']) => $schema['minLength'] ?? null,
                default => null,
            }"
            :maxlength="match ($schema['type']) {
                $matchesTypes($schema['type'], ['string']) => $schema['maxLength'] ?? null,
                default => null,
            }"
            :min="match ($schema['type']) {
                $matchesTypes($schema['type'], ['integer', 'number', 'float']) => $schema['minimum'] ?? null,
                default => null,
            }"
            :max="match ($schema['type']) {
                $matchesTypes($schema['type'], ['integer', 'number', 'float']) => $schema['maximum'] ?? null,
                default => null,
            }"
            :step="match ($schema['type']) {
                $matchesTypes($schema['type'], ['integer']) => $schema['multipleOf'] ?? 1,
                $matchesTypes($schema['type'], ['number', 'float']) => $schema['multipleOf'] ?? null,
                default => null,
            }"
            :placeholder="data_get($schema, 'magic_ui.placeholder') ?? $label"
            @input.debounce.500ms="
                if ({{ $matchesTypes(($schema['type'] ?? null), ['integer', 'number', 'float']) ? 'true' : 'false' }}) {
                    {{ $statePath }} = parseFloat({{ $statePath }});
                }

                $wire.validateData()
            "
        />
    @else
        <pre class="text-red-500">{{ json_encode([$name, $schema]) }}</pre>
    @endif
</div>
