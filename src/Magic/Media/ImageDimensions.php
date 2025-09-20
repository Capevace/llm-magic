<?php

namespace Mateffy\Magic\Media;

readonly class ImageDimensions
{
	public function __construct(
		public int $width,
		public int $height,
	) {
	}

    public static function square(int $size): self
    {
        return new self($size, $size);
    }

	public function toString(): string
	{
		return "{$this->width}x{$this->height}";
	}

	public function __toString(): string
	{
		return $this->toString();
	}

    public static function parse(string $text): ?self
    {
        if (preg_match('/^(\d+)x(\d+)$/', $text, $matches)) {
            return new self((int)$matches[1], (int)$matches[2]);
        }

        return null;
    }
}
