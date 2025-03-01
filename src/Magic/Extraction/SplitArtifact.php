<?php

namespace Mateffy\Magic\Extraction;

use Illuminate\Support\Collection;
use Mateffy\Magic\Chat\Messages\MultimodalMessage\Base64Image;
use Mateffy\Magic\Extraction\Artifacts\ArtifactMetadata;
use Mateffy\Magic\Extraction\Slices\EmbedSlice;
use Mateffy\Magic\Extraction\Slices\Slice;

/**
 * Artifact directory:
 * /artifacts/<ID>
 * /artifacts/<ID>/metadata.json
 * /artifacts/<ID>/source.<EXT>
 * /artifacts/<ID>/thumbnail.jpg
 * /artifacts/<ID>/embeds (optional)
 * /artifacts/<ID>/embeds/<FILENAME>.jpg
 */
readonly class SplitArtifact implements Artifact
{
    public function __construct(
        public Artifact $original,
        /** @var Collection<Slice> */
        public Collection $contents,
        public int $tokens
    ) {}

    public function getMetadata(): ArtifactMetadata
    {
        return $this->original->getMetadata();
    }

    public function getContents(?ContextOptions $filter = null): Collection
    {
        return $filter?->filter($this->contents) ?? $this->contents;
    }

    public function getText(): ?string
    {
        return $this->original->getText();
    }

    public function makeBase64Image(EmbedSlice $content): Base64Image
    {
        return $this->original->makeBase64Image($content);
    }

    /**
     * Splits the document. Adds data until either the character limit or embed limit is reached, then starts a new split.
     *
     * @return Artifact
	 */
    public function split(int $maxTokens): array
    {
        // Splitting is not supported for split artifacts
        return [$this];
    }

    public function getRawEmbedContents(EmbedSlice $content): mixed
    {
        return $this->original->getRawEmbedContents($content);
    }
}
