<?php

namespace Mateffy\Magic\Chat\Prompt;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Mateffy\Magic\Chat\Messages\MultimodalMessage\Base64Image;
use Mateffy\Magic\Extraction\Artifact;
use Mateffy\Magic\Extraction\ContextOptions;
use Mateffy\Magic\Extraction\Slices\EmbedSlice;
use Mateffy\Magic\Extraction\Slices\RawTextSlice;
use Mateffy\Magic\Extraction\Slices\Slice;

class ArtifactPromptFormatter
{
	public function __construct(
		/** @var Collection<Artifact> */
		protected Collection      $artifacts,
		protected ?ContextOptions $filter = null,
	)
	{
	}

	/**
	 * Format the text slices of the artifacts as XML
	 *
	 * @param Collection<Artifact>|array<Artifact> $artifacts
	 */
	public static function formatText(Collection|array $artifacts, ?ContextOptions $filter = null): string
	{
		// We use Laravel Service container to enable dependency injection of custom classes by the end user, if wanted
		$formatter = app(ArtifactPromptFormatter::class, [
			'artifacts' => Collection::wrap($artifacts),
			'filter' => $filter,
		]);

		return $formatter->toXML();
	}

	/**
	 * Format the embedded slices of the artifacts as Base64 images
	 * @return Collection<Base64Image>
	 */
	public static function formatImagesAsBase64(Collection|array $artifacts, ?ContextOptions $filter = null): Collection
	{
		// We use Laravel Service container to enable dependency injection of custom classes by the end user, if wanted
		$formatter = app(ArtifactPromptFormatter::class, [
			'artifacts' => Collection::wrap($artifacts),
			'filter' => $filter,
		]);

		return $formatter->convertEmbedsToBase64();
	}

	/**
	 * Convert each artifact's text slices to paged XML
	 *
	 * @return Collection<string>
	 */
	public function formatTextSlicesAsXML(): Collection
	{
		return collect($this->artifacts)
            ->map(function (Artifact $artifact) {
                $pages = $artifact->getContents(filter: $this->filter)
                    ->filter(fn ($content) => $content instanceof RawTextSlice)
                    ->groupBy(fn (RawTextSlice $content) => $content->page ?? 0)
                    ->sortBy(fn (Collection $contents, $page) => $page)
                    ->values()
                    ->flatMap(fn (Collection $contents) => collect($contents)
                        ->map(fn (RawTextSlice $content) => Blade::render("<page num=\"{{ \$content->page }}\">\n{{ \$content->text }}\n</page>", ['content' => $content]))
                    )
                    ->join("\n\n");

                return Blade::render(
                    <<<'BLADE'
                    <artifact name="{{ $name }}" >
                    {!! $pages !!}
                    </artifact>
                    BLADE,
                    ['name' => $artifact->getMetadata()->name, 'pages' => $pages]
                );
            })
            ->values();
	}

	/**
	 * Convert the embedded slices of the artifacts to Base64 images
	 *
	 * @return Collection<Base64Image>
	 */
	public function convertEmbedsToBase64(): Collection
	{
		return collect($this->artifacts)
			->flatMap(function (Artifact $artifact) {
				return $artifact->getContents(filter: $this->filter)
						->filter(fn (Slice $content) => $content instanceof EmbedSlice)
						->groupBy(fn (EmbedSlice $content) => $content->getPage() ?? 0)
						->sortBy(fn (Collection $slices, $page) => $page)
						->flatMap(fn (Collection $slices) =>
							collect($slices)
								->map(fn (EmbedSlice $image) => $artifact->makeBase64Image($image))
						);
			});
	}

	/**
	 * Convert the entire artifact collection to a string.
	 */
	public function toXML(): string
	{
		$artifacts = $this->formatTextSlicesAsXML()->join("\n");

		return <<<XML
		<artifacts>
		{$artifacts}
		</artifacts>
		XML;
	}
}