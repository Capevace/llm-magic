@props([
	'runId',
	'statePath',
	'limitWidth' => false,
	'removableFrom' => null,
	'removableIndex' => null,
])

<div class="relative p-2 flex justify-center">
	<img
		class="object-contain rounded w-min"
		@if ($limitWidth)
			style="max-height: 6rem; max-width: 12rem;"
		@endif
		x-show="!!{{ $statePath }}"
		x-data="{
			get url () {
				const base = `/runs/{{ $runId }}/artifacts/`;
				const id = this.{{ $statePath }};

				return `${base}?artifactId=${id}`;
			}
		}"
		:src="url"
	/>

	@if ($removableFrom && $removableIndex)
		<nav class="absolute top-0 right-0 p-3">
			<x-filament::icon-button
				color="danger"
				size="xs"
				icon="heroicon-o-x-mark"
				icon-position="after"
				:tooltip="__('Remove')"
				@click="
					{{ $removableFrom }}.splice({{ $removableIndex }}, 1);
					$wire.validateData();
				"
			/>
		</nav>
	@endif
</div>