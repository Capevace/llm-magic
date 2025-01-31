<?php

namespace Mateffy\Magic\Artifacts;

use Illuminate\Support\Collection;
use Mateffy\Magic\Artifacts\Content\Content;
use Mateffy\Magic\Artifacts\Content\EmbedContent;
use Mateffy\Magic\LLM\Message\MultimodalMessage\Base64Image;

interface Artifact
{
    public function getMetadata(): ArtifactMetadata;

    /**
     * @return array<Content>
     */
    public function getContents(): array;

    public function getText(): ?string;
    public function getBase64Images(?int $maxPages = null): Collection;
    public function getEmbedContents(EmbedContent $content): mixed;
    public function makeBase64Image(EmbedContent $content): Base64Image;

    /**
     * @return {0: array<Artifact>, 1: int}
     */
    public function split(int $maxTokens): array;
}
