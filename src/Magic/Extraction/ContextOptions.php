<?php

namespace Mateffy\Magic\Extraction;

use Illuminate\Support\Collection;
use Mateffy\Magic\Extraction\Slices\EmbedSlice;
use Mateffy\Magic\Extraction\Slices\ImageSlice;
use Mateffy\Magic\Extraction\Slices\RawTextSlice;
use Mateffy\Magic\Extraction\Slices\Slice;

/**
 * A content filter is used to determine, what contents of an Artifact to include in an LLM call.
 */
class ContextOptions
{
	public function __construct(
		protected bool $includeText,
		protected bool $includeEmbeddedImages,
		protected bool $includePageImages,
		protected bool $markPageImages,
	)
	{
	}

	/**
	 * Create a content filter with everything included.
	 */
	public static function all(): ContextOptions
	{
		return new ContextOptions(
			includeText: true,
			includeEmbeddedImages: true,
			includePageImages: true,
			markPageImages: true,
		);
	}

	public static function default(): ContextOptions
	{
		return new ContextOptions(
			includeText: true,
			includeEmbeddedImages: true,
			includePageImages: false,
			markPageImages: false,
		);
	}

	/**
	 * Filter a content collection according to the filter settings.
	 *
	 * @param Collection<Slice> $contents
	 * @return Collection<Slice>
	 */
	public function filter(Collection $contents): Collection
	{
		return $contents->filter(fn (Slice $content) => match (true) {
			$this->includeText && $content instanceof RawTextSlice,
			$this->includePageImages && $content instanceof ImageSlice && $content->page !== null,
			$this->includeEmbeddedImages && $content instanceof EmbedSlice,
			$this->markPageImages && $content instanceof ImageSlice && $content->page === null => true,
			default => false,
		});
	}
}