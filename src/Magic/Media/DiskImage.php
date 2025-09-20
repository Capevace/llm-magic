<?php

namespace Mateffy\Magic\Media;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

readonly class DiskImage implements Image
{
	public function __construct(
		public string $disk,
		public string $path,
	)
	{
	}

	public function getMime(): string
	{
		return Storage::disk($this->disk)->mimeType($this->path);
	}

	public function getContents(): mixed
	{
		$contents = Storage::disk($this->disk)->get($this->path);

        if ($contents === false) {
            throw new RuntimeException("Failed to get contents from disk: {$this->disk}, path: {$this->path}");
        }

        return $contents;
	}

	public function getStream(): mixed
	{
		$stream = Storage::disk($this->disk)->readStream($this->path);

		if ($stream === null) {
			throw new RuntimeException("Failed to get stream from disk: {$this->disk}, path: {$this->path}");
		}

		return $stream;
	}

    public static function make(string $disk, string $path): static
	{
		return new self($disk, $path);
	}

	public static function create(Image $original, string $disk, string $path): static
	{
		Storage::disk($disk)->put($path, $original->getContents());

		return new self($disk, $path);
	}
}
