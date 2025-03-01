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
 * /magic-artifact-<ID> - The base directory
 * /magic-artifact-<ID>/metadata.json - Metadata about the artifact
 * /magic-artifact-<ID>/contents.json - The extracted slices of the artifact
 * /magic-artifact-<ID>/source.<EXT> - The original source file
 * /magic-artifact-<ID>/source.txt - The complete text content of the artifact
 * /magic-artifact-<ID>/marked.pdf (optional) - The marked up version of the source file (only PDF)
 * /magic-artifact-<ID>/images (optional) - Directory for extracted images
 * /magic-artifact-<ID>/images/image<NUM>.jpg
 * /magic-artifact-<ID>/pages (optional) - Directory for extracted page "screenshots"
 * /magic-artifact-<ID>/pages/page<NUM>.jpg
 * /magic-artifact-<ID>/pages_marked (optional) - Directory for extracted page "screenshots" with embedded images marked
 * /magic-artifact-<ID>/pages_marked/page<NUM>.jpg
 * /magic-artifact-<ID>/pages_txt (optional) - Directory for extracted page text content
 * /magic-artifact-<ID>/pages_txt/page<NUM>.txt
 */
class DiskArtifact implements Artifact
{
    protected ?ArtifactMetadata $metadata = null;
    protected ?Collection $contents = null;

    public bool $cache;
    public readonly string $artifactDir;
    public readonly ?string $artifactDirDisk;

    public static function from(string $path, ?string $disk = null): static
    {
        $artifactDisk = config('llm-magic.artifacts.disk');
        $prefix = config('llm-magic.artifacts.prefix');
        $base = config('llm-magic.artifacts.base', storage_path('app/magic-artifacts'));

        $artifactDir = $artifactDisk
            ? $prefix
            : $base;

        return new static(
            path: $path,
            disk: $disk,
            artifactDir: $artifactDir,
            artifactDirDisk: $artifactDisk,
        );
    }

    protected function __construct(
        public readonly string $path,
        /**
         * Absolute path to the directory where the artifact will be stored.
         * If a artifactDirDisk is provided,
         */
        public readonly ?string $disk = null,
        ?string $artifactDir = null,
        ?string $artifactDirDisk = null,
        ?string $artifactDirName = null,
    )
    {
        $artifactDirName ??= "magic-artifact-" . crc32("{$this->disk}:{$this->path}");

        $makeArtifactDir = fn (string $dir) => collect([$dir, $artifactDirName])
            ->filter(fn ($part) => filled($part))
            ->implode("/");

        if ($artifactDir !== null) {
            $this->cache = true;
            $this->artifactDirDisk = $artifactDirDisk;
            $this->artifactDir = $makeArtifactDir($artifactDir);
        } else {
            $this->cache = false;
            $this->artifactDirDisk = null;
            $this->artifactDir = $makeArtifactDir(sys_get_temp_dir());
        }
    }

    public function getSourcePath(): string
    {
        return $this->path;
    }

    protected function getRawContents(): mixed
    {
        return $this->disk
            ? Storage::disk($this->disk)->get($this->path)
            : File::get($this->path);
    }

    /**
     * @return Collection<Slice>
     * @throws \Mateffy\Magic\Artifacts\ArtifactGenerationFailed
	 * @throws JsonException
     */
    public function getContents(?ContextOptions $filter = null): Collection
    {
        $this->contents ??= match ($this->getMetadata()->type) {
            ArtifactType::Text,
            ArtifactType::Image => $this->getFlatFileContents(),
            ArtifactType::Pdf => $this->getPdfContents(),
            default => [],
        };

		return $filter?->filter($this->contents) ?? $this->contents;
    }

    /**
     * @throws JsonException
     * @throws ArtifactGenerationFailed
	 * @return Collection<Slice>
     */
    public function refreshContents(): Collection
    {
        $this->contents = null;
		$this->cache = false;

        $contents = $this->getContents();

		$this->cache = true;

		return $contents;
    }

    protected function findExistingArtifactDir(): ?string
    {
        $disk = $this->artifactDirDisk
            ? Storage::disk($this->artifactDirDisk)
            : null;

        if ($this->cache) {
            if ($disk?->exists($this->artifactDir)) {
                return $this->artifactDir;
            } elseif (File::exists($this->artifactDir)) {
                return $this->artifactDir;
            }
        }

        return null;
    }

	/**
	 * @return Collection<Slice>
	 * @throws JsonException
	 */
    protected function parseContentSlices(string $outputDir): Collection
    {
        $disk = $this->artifactDirDisk
            ? Storage::disk($this->artifactDirDisk)
            : null;

        if ($disk) {
            $contents = $disk->get("{$outputDir}/contents.json") ?? '[]';
            $data = json_decode($contents, true, flags: JSON_THROW_ON_ERROR);
        } else {
            $data = File::json("{$outputDir}/contents.json", flags: JSON_THROW_ON_ERROR);
        }

        return collect($data)
            ->map(fn (array $content) => match ($content['type']) {
                'text' => RawTextSlice::from($content),
                'image', 'page-image',
                'page-image-marked' => ImageSlice::from($content),
                default => null
            })
            ->filter()
            ->sortBy(fn (Slice $content) => $content->getPage() ?? 0)
            ->values();
    }

	/**
	 * @throws JsonException
	 * @throws \Mateffy\Magic\Artifacts\ArtifactGenerationFailed
	 * @return Collection<Slice>
	 */
    protected function getPdfContents(): Collection
    {
        $outputDir = $this->findExistingArtifactDir();

        // No cached path found, generate
        if ($outputDir === null) {
            $parser = new PdfParser(path: $this->path, disk: $this->disk);
            $parser->parse();

            if ($this->artifactDirDisk) {
				$outputDir = $parser->moveToStorage(disk: $this->artifactDirDisk, path: $this->artifactDir);
			} else {
				$outputDir = $parser->moveToPath(path: $this->artifactDir);
			}
        }

        return $this->parseContentSlices($outputDir);
    }

	/**
	 * If an image is provided, we fake the processing process but still write the image to the artifact directory.
	 * @throws JsonException
	 * @return Collection<Slice>
	 */
    protected function getFlatFileContents(): Collection
    {
        $disk = $this->artifactDirDisk
            ? Storage::disk($this->artifactDirDisk)
            : null;

        $outputDir = $this->findExistingArtifactDir();

        if ($outputDir === null) {
            $outputDir = $this->artifactDir;

            if ($this->cache) {
				$slice = match ($this->getMetadata()->type) {
					default => new RawTextSlice($this->getRawContents()),
					ArtifactType::Image => new ImageSlice(
						type: 'image',
						mimetype: $this->getMetadata()->mimetype,
						path: "source.{$this->getMetadata()->extension}",
					)
				};

                if ($this->artifactDirDisk) {
                    $disk->makeDirectory($this->artifactDir);
                    $disk->put("{$this->artifactDir}/metadata.json", json_encode($this->getMetadata()->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
                    $disk->put("{$this->artifactDir}/contents.json", json_encode([$slice->toArray()], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
                    $disk->put("{$this->artifactDir}/source.{$this->getMetadata()->extension}", $this->getRawContents());
                } else {
                    File::ensureDirectoryExists($this->artifactDir);
					File::put("{$this->artifactDir}/metadata.json", json_encode($this->getMetadata()->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
					File::put("{$this->artifactDir}/contents.json", json_encode([$slice->toArray()], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
					File::put("{$this->artifactDir}/source.{$this->getMetadata()->extension}", $this->getRawContents());
                }
            }
        }

        return $this->parseContentSlices($outputDir);
    }

    /**
     * @throws JsonException
     */
    public function getText(?int $maxPages = null): ?string
    {
        return collect($this->getContents())
            ->filter(fn (Slice $content) => $content instanceof TextualSlice)
            ->groupBy(fn (TextualSlice $content) => $content->getPage() ?? 0)
            ->sortBy(fn (Collection $contents, $page) => $page)
            ->values()
            ->take($maxPages)
            ->flatMap(fn (Collection $contents) => collect($contents)
                ->map(fn (TextualSlice $content) => "<page num=\"{$content->page}\">\n{$content->text}\n</page>")
            )
            ->join("\n");
    }

    public function getMetadata(): ArtifactMetadata
    {
        return $this->metadata
            ?: $this->metadata = ArtifactMetadata::fromFile(path: $this->path, disk: $this->disk);
    }

    public function makeBase64Image(EmbedSlice $content): Base64Image
    {
        $path = $content->isAbsolutePath()
            ? $content->getPath()
            : "{$this->artifactDir}/{$content->getPath()}";

        return $this->disk
            ? Base64Image::fromDisk($this->artifactDirDisk, $path)
            : Base64Image::fromPath($path);
    }

    public function getRawEmbedContents(EmbedSlice $content): mixed
    {
        $path = $content->isAbsolutePath()
            ? $content->getPath()
            : "{$this->artifactDir}/{$content->getPath()}";

        return $this->disk
            ? Storage::disk($this->artifactDirDisk)->get($path)
            : File::get($path);
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
