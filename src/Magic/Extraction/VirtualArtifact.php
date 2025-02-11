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


/**
 * Artifact directory:
 * /artifacts/<ID>
 * /artifacts/<ID>/metadata.json
 * /artifacts/<ID>/source.<EXT>
 * /artifacts/<ID>/thumbnail.jpg
 * /artifacts/<ID>/embeds (optional)
 * /artifacts/<ID>/embeds/<FILENAME>.jpg
 */
class VirtualArtifact implements Artifact
{
    protected ?ArtifactMetadata $metadata = null;
    protected ?array $contents = null;

    public readonly bool $cache;
    public readonly string $artifactDir;
    public readonly ?string $artifactDirDisk;

    public function __construct(
		ArtifactMetadata $metadata,
		protected string $rawContents,
		?array $contents,
		protected array $pdfContents = [],
		protected ?string $text = null,
	)
    {
		$this->metadata = $metadata;
		$this->contents = $contents;
    }

    public function getSourcePath(): string
    {
        return $this->metadata->path;
    }

    protected function getRawContents(): mixed
    {
		return $this->rawContents;
    }

    /**
     * @throws JsonException
     */
    public function getContents(): array
    {
		return $this->contents;
    }

    /**
     * @throws JsonException
     */
    public function refreshContents(): array
    {
        return $this->getContents();
    }

    /**
     * @throws JsonException
     */
    protected function getPdfContents(): array
    {
        return $this->pdfContents;
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

    public function getBase64Images(?int $maxPages = null): Collection
    {
        return collect([]);
    }

    public function getEmbedContents(EmbedSlice $content): mixed
    {
        return null;
    }

    /**
     * Splits the document. Adds data until either the character limit or embed limit is reached, then starts a new split.
     *
     * @return {0: array<array<Artifact>>, 1: int}
     */
    public function split(int $maxTokens): array
    {
        $artifacts = [];
        $contents = [];

        $tokens = 0;
        $totalTokens = 0;

        foreach ($this->getContents() as $content) {
            if ($content instanceof TextualSlice) {
                $textLength = strlen($content->text());

                // TODO: Actually count tokens, because 1 character !== 1 token
                // TODO: We can also split the text, if the tokens for this text are higher than the maxTokens
                $tokens += $textLength;
                $totalTokens += $textLength;

                $contents[] = $content;
            } elseif ($content instanceof EmbedSlice) {
                if ($content instanceof ImageSlice && $content->width && $content->height) {
                    // Based on Anthropic's model: tokens = (width px * height px)/750
                    // This will not be accurate for other LLMs but is good enough for now
                    $tokens += ($content->width * $content->height) / 750;
                    $totalTokens += ($content->width * $content->height) / 750;
                    // 3:4	951x1268 px
                } else {
                    // Based on Anthropic's model: we use the average for a 1000x1000 image
                    $tokens += 1334;
                    $totalTokens += 1334;
                }

                $contents[] = $content;
            }

            if ($tokens > $maxTokens) {
                $artifacts[] = new SplitArtifact(original: $this, contents: $contents, tokens: $tokens);
                $contents = [];

                $tokens = 0;
            }
        }

        if (! empty($contents)) {
            $artifacts[] = new SplitArtifact(original: $this, contents: $contents, tokens: $tokens);
        }

        return [$artifacts, $totalTokens];
    }
}
