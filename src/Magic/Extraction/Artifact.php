<?php

namespace Mateffy\Magic\Extraction;

use Illuminate\Support\Collection;
use Mateffy\Magic\Chat\Messages\MultimodalMessage\Base64Image;
use Mateffy\Magic\Extraction\Artifacts\ArtifactMetadata;
use Mateffy\Magic\Extraction\Slices\EmbedSlice;
use Mateffy\Magic\Extraction\Slices\Slice;

interface Artifact
{
    public function getMetadata(): ArtifactMetadata;

    /**
     * @return array<Slice>
     */
    public function getContents(): array;

    public function getText(): ?string;
    public function getBase64Images(?int $maxPages = null): Collection;
    public function getEmbedContents(EmbedSlice $content): mixed;
    public function makeBase64Image(EmbedSlice $content): Base64Image;

    /**
     * @return {0: array<Artifact>, 1: int}
     */
    public function split(int $maxTokens): array;
}
