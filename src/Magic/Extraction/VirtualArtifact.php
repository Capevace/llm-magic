<?php

namespace Mateffy\Magic\Extraction;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use JsonException;
use Mateffy\Magic\Chat\Messages\MultimodalMessage\Base64Image;
use Mateffy\Magic\Exceptions\ArtifactGenerationFailed;
use Mateffy\Magic\Extraction\Artifacts\ArtifactMetadata;
use Mateffy\Magic\Extraction\Artifacts\ArtifactType;
use Mateffy\Magic\Extraction\Parsers\PdfParser;
use Mateffy\Magic\Extraction\Slices\EmbedSlice;
use Mateffy\Magic\Extraction\Slices\ImageSlice;
use Mateffy\Magic\Extraction\Slices\RawTextSlice;
use Mateffy\Magic\Extraction\Slices\Slice;
use Mateffy\Magic\Extraction\Slices\TextualSlice;


class VirtualArtifact implements Artifact
{
    public function __construct(
		protected ArtifactMetadata $metadata,
		protected string $rawContents,
		protected ?Collection $contents,
		protected ?string $text = null,
	)
    {
    }

    public function getSourcePath(): string
    {
        return $this->metadata->path;
    }

    protected function getRawContents(): mixed
    {
		return $this->rawContents;
    }

    public function getContents(?ContextOptions $filter = null): Collection
    {
		return $filter?->filter($this->contents) ?? $this->contents;
    }

    /**
     * @throws JsonException
     */
    public function getText(?int $maxPages = null): ?string
    {
        return $this->text;
    }

    public function getMetadata(): ArtifactMetadata
    {
        return $this->metadata;
    }

    public function makeBase64Image(EmbedSlice $content): Base64Image
    {
		throw new \Exception('Not implemented');
    }

    public function getRawEmbedContents(EmbedSlice $content): mixed
    {
        return null;
    }

    /**
     * Splits the document. Adds data until either the character limit or embed limit is reached, then starts a new split.
     *
     * @return array{0: Collection<Collection<Artifact>>, 1: int}
     */
    public function split(int $maxTokens, ?ContextOptions $filter = null): array
    {
        return ArtifactSplitter::split(
			artifact: $this,
			contents: $this->getContents(filter: $filter),
			maxTokens: $maxTokens,
		);
    }
}
