@props([
    'color' => 'gray',
    'label' => 'Daten',
    'name' => '',
    'statePath',
    'rootStatePath',
    'validationRootStatePath',
    'schema' => [
        'type' => 'string',
        'enum' => ['test'],
    ],
    'required' => false,
    'disabled' => false,
    'removableFrom' => null,
    'removableIndex' => null,
])

<?php
  $matchesTypes = function (array|string|null $type, array $typesToMatch) {
      if (is_array($type)) {
          $type = $type[0];
      }

      return in_array($type, $typesToMatch);
  };

  $getMagicUiTableConfig = function (array $schema) {
      $config = $schema['magic_ui'] ?? null;

      if (is_array($config) && $config['type'] === 'table') {
          return $config;
      } else if ($config === 'table') {
          return ['type' => 'table'];
      }
  };

  $getMagicUiLabel = function (array $schema) {
      $config = $schema['magic_ui'] ?? null;

      if (is_array($config) && $config['label'] ?? null) {
          return $config['label'];
      }

      return null;
  };

  if (($schema['type'] ?? null) === null) {
      \Illuminate\Support\Facades\Log::error('Property type is null', [
          'schema' => $schema,
      ]);
  }

  $validationPath = str($statePath)
    ->replace($rootStatePath, $validationRootStatePath)
    ->toString();
?>

<div
    {{ $attributes }}
    x-data="{
        get error() {
            try {
                return this.{!! $validationPath !!};
            } catch (e) {
                return false;
            }
        },
    }"
>
    @if (($schema['type'] ?? null) === 'array')
        <div class="grid grid-cols-1 gap-4 col-span-full">
            <div>
                <x-filament-forms::field-wrapper.label :$required>
                    {{ $getMagicUiLabel($schema) ?? \Illuminate\Support\Str::title($label) }}
                </x-filament-forms::field-wrapper.label>

                @if ($description = $schema['description'] ?? null)
                    <p class="text-sm text-gray-500 dark:text-gray-400 col-span-full">
                        {{ $description }}
                    </p>
                @endif
            </div>

            @if ($table = $getMagicUiTableConfig($schema))
                <x-llm-magic::json-schema.table
                    :name="$name"
                    :state-path="$statePath"
                    :root-state-path="$rootStatePath"
                    :validation-root-state-path="$validationRootStatePath"
                    :schema="$schema['items']"
                    :disabled="$disabled"
                />
            @else
                <x-llm-magic::json-schema.loop :name="$name" :state-path="$statePath" class="grid gap-5">
                    <x-llm-magic::json-schema.property
                        x-bind:id="{{ $name }}_index + '_' + hashObject({{ $name }})"
                        class="col-span-full"
                        :label="$getMagicUiLabel($schema) ?? \Illuminate\Support\Str::singular($label)"
                        :name="$name . '_0'"
                        :state-path="$statePath . '[' . $name . '_index]'"
                        :root-state-path="$rootStatePath"
                        :validation-root-state-path="$validationRootStatePath"
                        :schema="$schema['items']"
                        :required="true"
                        :disabled="$disabled"
                        :removable-from="$statePath"
                        :removable-index="$name . '_index'"
                    />
                </x-llm-magic::json-schema.loop>
                <?php
                    $hiddenBecauseImage = \Illuminate\Support\Arr::get($schema, 'items.format') === 'artifact-id';
                    $defaultData = collect(\Illuminate\Support\Arr::get($schema, 'items.properties'))
                            ->mapWithKeys(function ($propertySchema, $property) {
                                $type = $propertySchema['type'] ?? 'string';
                                $nullable = $type === 'null' || (is_array($type) && in_array('null', $type));

                                return [$property => $nullable ? null : ''];
                            })
                            ->toArray();

                    $defaultDataBase64 = base64_encode(json_encode($defaultData));
                ?>

                @if (!$hiddenBecauseImage)
                    <nav class="flex w-full justify-center">
                        <x-filament::button
                            color="gray"
                            size="xs"
                            icon="heroicon-o-plus"
                            icon-position="after"
                            @click="
                                const defaultData = JSON.parse(atob('{{ $defaultDataBase64 }}'));

                                if (!Array.isArray({{ $statePath }})) {
                                    {{ $statePath }} = [];
                                }

                                {{ $statePath }}.push(defaultData);
                            "
                        >
                            {{ __('Add') }}
                        </x-filament::button>
                    </nav>
                @endif
            @endif
        </div>
    @elseif ($matchesTypes(($schema['type'] ?? null), ['object']))
        @if (count(($schema['properties'] ?? [])) === 1 && ($property = array_keys(($schema['properties'] ?? []))[0]))
            <?php
                $custom_label = data_get($schema['properties'][$property], 'magic_ui.label');
                $label = $custom_label ?? str($property)->title()->replace('_', ' ')->__toString();

                $propertySchema = ($schema['properties'] ?? [])[$property];
                $propertyType = $propertySchema['type'] ?? 'string'; // We assume string if no type is defined
                // Required in this context means the property needs to be present, but it may still be nullable
                $required = collect($schema['required'])->contains($property);
                $nullable = $propertySchema['type'] === 'null' || (is_array($propertySchema['type']) && in_array('null', $propertySchema['type']));
            ?>
            <x-llm-magic::json-schema.property
                :label="$label"
                :name="$name . '_' . $property"
                :state-path="$statePath . '.' . $property"
                :root-state-path="$rootStatePath"
                :validation-root-state-path="$validationRootStatePath"
                :schema="$propertySchema"
                :required="$required && !$nullable"
                :disabled="$disabled"
                :removable-from="$statePath"
                :removable-index="$name . '_index'"
            />
        @else
            <article
                @class(["shadow-sm grid grid-cols-2 gap-x-5 border border-{$color}-400/30 dark:border-{$color}-700 bg-gradient-to-br from-{$color}-50/80 to-{$color}-200/80 dark:from-{$color}-800/50 dark:to-{$color}-900/50 rounded"])
            >
                <header class="border-b border-{{ $color }}-400/50 dark:border-{{ $color }}-700 flex items-center justify-between col-span-full px-3 py-2">
                    <h3 class="text-sm font-semibold">{{ $getMagicUiLabel($schema) ?? str($label)->singular()->title() }}</h3>

                    @if ($removableFrom && $removableIndex)
                        <x-filament::icon-button
                            icon="heroicon-o-x-mark"
                            color="danger"
                            size="xs"
                            class="w-min flex-shrink-0"
                            @click="
                                {{ $removableFrom }}.splice({{ $removableIndex }}, 1);
                                $wire.validateData();
                            "
                            :tooltip="__('Remove')"
                        />
                    @endif
                </header>

                @if ($description = $schema['description'] ?? null)
                    <p class="text-sm text-gray-500 dark:text-gray-400 col-span-full px-5 py-3">
                        {{ $description }}
                    </p>
                @endif

                @foreach(($schema['properties'] ?? []) as $property => $propertySchema)
                    <?php
                        $custom_label = data_get($propertySchema, 'magic_ui.label');
                        $label = $custom_label ?? str($property)->title()->replace('_', ' ')->__toString();

                        $propertyType = $propertySchema['type'] ?? 'string'; // We assume string if no type is defined
                        // Required in this context means the property needs to be present, but it may still be nullable
                        $required = collect($schema['required'])->contains($property);
                        $nullable = $propertySchema['type'] === 'null' || (is_array($propertySchema['type']) && in_array('null', $propertySchema['type']));
                    ?>
                    <div
                        wire:key="{{ $name . '_' . $property }}"
                        x-data="{ '{{ $name . '_' . $property }}': '{{ $statePath }}.{{ $property }}' }"
                        @class([
                            'px-5 py-3',
                            'col-span-1' => !$matchesTypes($propertySchema['type'] ?? '', ['object', 'array']),
                            'col-span-full' => $matchesTypes($propertySchema['type'] ?? '', ['object', 'array']),
                        ])
                    >
                        <x-llm-magic::json-schema.property
                            :label="$label"
                            :name="$name . '_' . $property"
                            :state-path="$statePath . '.' . $property"
                            :root-state-path="$rootStatePath"
                            :validation-root-state-path="$validationRootStatePath"
                            :schema="$propertySchema"
                            :required="$required && !$nullable"
                            :disabled="$disabled"
                        />
                    </div>
                @endforeach
            </article>
        @endif
    @elseif ($matchesTypes(($schema['type'] ?? null), ['integer', 'number', 'float', 'string']))
        <x-filament-forms::field-wrapper.label :$required>
            {{ $getMagicUiLabel($schema) ?? \Illuminate\Support\Str::title($label) }}
        </x-filament-forms::field-wrapper.label>

        @if (data_get($schema, 'magic_ui.component') === 'textarea' || data_get($schema, 'magic_ui') === 'textarea')
            <x-filament::input.wrapper class="fi-fo-textarea overflow-hidden">
                <textarea
                    name="{{ $statePath }}"
                    x-model="{{ $statePath }}"
                    @if ($disabled)
                        disabled
                    @endif

                    @if ($required)
                        required
                    @endif

                    @if ($matchesTypes(($schema['type'] ?? null), ['string']) && ($schema['minLength'] ?? null))
                        minlength="{{ $schema['minLength'] }}"
                    @endif

                    @if ($matchesTypes(($schema['type'] ?? null), ['string']) && ($schema['maxLength'] ?? null))
                        maxlength="{{ $schema['maxLength'] }}"
                    @endif

                    placeholder="{{ data_get($schema, 'magic_ui.placeholder') ?? $label }}"

                    @class([
                        'block w-full h-full border-none bg-transparent px-3 py-1.5 text-base text-gray-950 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6',
                    ])
                    @input.debounce.500ms="
                        if ({{ $matchesTypes(($schema['type'] ?? null), ['integer', 'number', 'float']) ? 'true' : 'false' }}) {
                            {{ $statePath }} = parseFloat({{ $statePath }});
                        }

                        $wire.validateData()
                    "
                ></textarea>
            </x-filament::input.wrapper>
        @elseif ($enum = ($schema['enum'] ?? null))
            <x-filament::input.wrapper>
                <x-filament::input.select
                    name="{{ $statePath }}"
                    x-model="{{ $statePath }}"
                    placeholder="{{ data_get($schema, 'magic_ui.placeholder') ?? $label }}"

                    :disabled="$disabled ? true : false"
                    :required="$required ? true : false"
                    @change="$wire.validateData()"
                >
                    @foreach ($enum as $value)
                        <option
                            wire:key="{{ $value }}"
                            value="{{ $value }}"
                        >{{ $value }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        @elseif (($schema['format'] ?? null) === 'artifact-id')
            <x-llm-magic::json-schema.artifact-image :run-id="$this->runId" :state-path="$statePath" :removable-from="$removableFrom" :removable-index="$removableIndex" />
        @else
            <div class="flex items-center gap-2">
                <x-filament::input.wrapper
                    class="flex-1"
                    x-bind:style="error
                        ? '--tw-ring-color: rgba(var(--danger-500), 0.7)'
                        : ''
                    "
                >
                    <x-filament::input
                        name="{{ $statePath }}"
                        x-model="{{ $statePath }}"
                        :$disabled
                        :$required
                        :type="match (true) {
                            $matchesTypes(($schema['type'] ?? null), ['integer', 'number', 'float']) => 'number',
                            $matchesTypes(($schema['type'] ?? null), ['string']) => 'text',
                            default => 'text',
                        }"
                        :minlength="match (true) {
                            $matchesTypes(($schema['type'] ?? null), ['string']) => $schema['minLength'] ?? null,
                            default => null,
                        }"
                        :maxlength="match (($schema['type'] ?? null)) {
                            $matchesTypes(($schema['type'] ?? null), ['string']) => $schema['maxLength'] ?? null,
                            default => null,
                        }"
                        :min="match (($schema['type'] ?? null)) {
                            $matchesTypes(($schema['type'] ?? null), ['integer', 'number', 'float']) => $schema['minimum'] ?? null,
                            default => null,
                        }"
                        :max="match (($schema['type'] ?? null)) {
                            $matchesTypes(($schema['type'] ?? null), ['integer', 'number', 'float']) => $schema['maximum'] ?? null,
                            default => null,
                        }"
                        :step="match (($schema['type'] ?? null)) {
                            $matchesTypes(($schema['type'] ?? null), ['integer']) => $schema['multipleOf'] ?? 1,
                            $matchesTypes(($schema['type'] ?? null), ['number', 'float']) => $schema['multipleOf'] ?? null,
                            default => null,
                        }"
                        placeholder="{{ data_get($schema, 'magic_ui.placeholder') ?? $label }}"
                        @input.debounce.500ms="
                            if ({{ $matchesTypes(($schema['type'] ?? null), ['integer', 'number', 'float']) ? 'true' : 'false' }}) {
                                {{ $statePath }} = parseFloat({{ $statePath }});
                            }

                            $wire.validateData()
                        "
                    />
                </x-filament::input.wrapper>

                @if ($removableFrom && $removableIndex)
                    <x-filament::icon-button
                        color="danger"
                        size="xs"
                        icon="heroicon-o-x-mark"
                        icon-position="after"
                        class="flex-shrink-0"
                        @click="
                            {{ $removableFrom }}.splice({{ $removableIndex }}, 1);
                            $wire.validateData();
                        "
                    />
                @endif
            </div>

            <x-filament-forms::field-wrapper.error-message class="max-w-sm mt-1" x-show="error" x-cloak>
                <span x-text="error"></span>
            </x-filament-forms::field-wrapper.error-message>
        @endif
    @elseif ($matchesTypes(($schema['type'] ?? null), ['boolean']))
        <x-filament-forms::field-wrapper.label :$required>
            <div class="inline-flex items-center gap-2">
                <x-filament::input.checkbox
                    x-model="{{ $statePath }}"
                    @change="$wire.validateData()"
                    :$disabled
                    :$required
                    :label="$name"
                />

                <span>{{ $getMagicUiLabel($schema) ?? \Illuminate\Support\Str::title($label) }}</span>
            </div>
        </x-filament-forms::field-wrapper.label>
        <x-filament-forms::field-wrapper.error-message class="max-w-sm mt-1" x-show="error" x-cloak>
            <span x-text="error"></span>
        </x-filament-forms::field-wrapper.error-message>
    @else
        <pre class="text-red-500">{{ json_encode([$name, $schema]) }}</pre>
    @endif
</div>
