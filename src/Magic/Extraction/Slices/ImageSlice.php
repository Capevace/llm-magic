<?php

namespace Mateffy\Magic\Extraction\Slices;

readonly class ImageSlice implements Slice, EmbedSlice
{
    public function __construct(
		public string $type,
        public string $mimetype,
        public string $path,
        public ?int $page = null,
        public ?int $width = null,
        public ?int $height = null,
        public bool $absolutePath = false,
    ) {
    }

    public function toArray(): array
    {
        return [
			'type' => $this->type,
            'mimetype' => $this->mimetype,
            'path' => $this->path,
            'page' => $this->page,
            'width' => $this->width,
            'height' => $this->height,
        ];
    }

    public static function from(array $data): static
    {
        return new static(
			type: $data['type'],
            mimetype: $data['mimetype'],
            path: $data['path'],
            page: $data['page'] ?? null,
            width: $data['width'] ?? null,
            height: $data['height'] ?? null,
        );
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMimeType(): string
    {
        return $this->mimetype;
    }

    public function isAbsolutePath(): string
    {
        return $this->absolutePath;
    }

	public function getTokens(): int
	{
		return $this->calculateTokensWithOpenAI($this->width, $this->height);
	}

	public static function calculateTokensWithOpenAI(?int $width, ?int $height): int
	{
		// We use the OpenAI method of calculating tokens for images. https://platform.openai.com/docs/guides/vision#calculating-costs
		// This will not be accurate for all LLM providers and can be improved in the future.

		$width ??= 2048;
		$height ??= 2048;

		// Step 1: Scale to fit within 2048x2048 while maintaining aspect ratio
		$maxSize = 2048;
		if ($width > $maxSize || $height > $maxSize) {
			$scale = $maxSize / max($width, $height);
			$width = (int) round($width * $scale);
			$height = (int) round($height * $scale);
		}

		// Step 2: Scale shortest side to at least 768px
		$minSize = 768;
		if (min($width, $height) > $minSize) {
			$scale = $minSize / min($width, $height);
			$width = (int) round($width * $scale);
			$height = (int) round($height * $scale);
		}

		// Step 3: Calculate the number of 512px tiles required
		$tiles = ceil($width / 512) * ceil($height / 512);

		// Step 4: Compute the final token cost
		return ($tiles * 170) + 85;
	}

	public static function calculateTokensWithAnthropic(?int $width, ?int $height): int
	{
		$width ??= 2048;
		$height ??= 2048;

		// Based on Anthropic's model: tokens = (width px * height px)/750
		// This will not be accurate for other LLMs but is good enough for now
		return (int) ($width * $height) / 750;
	}
}
