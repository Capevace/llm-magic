<?php

namespace Mateffy\Magic\Media;

readonly class InMemoryImage implements Image
{
	public function __construct(
		public mixed $contents,
		public string $mime,
	)
	{
	}

	public function getMime(): string
	{
		return $this->mime;
	}

	public function getContents(): mixed
	{
		return $this->contents;
	}

	public function getStream(): mixed
	{
		$stream = fopen('php://temp', 'r+');

		if ($stream === false) {
			throw new \RuntimeException("Failed to create stream");
		}

		fwrite($stream, $this->contents);
		rewind($stream);

		return $stream;
	}
}