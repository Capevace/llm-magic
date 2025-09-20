<?php

namespace Mateffy\Magic\Models;

use Illuminate\Support\Collection;
use Mateffy\Magic\Media\Image;

interface ImageModel
{
	/**
	 * @param ?Collection<Image> $images
	 */
	public function generate(
		string $prompt,
		?Collection $images = null,
	): Image;

	/**
	 * @param ?Collection<Image> $images
	 * @return Collection<Image>
	 */
	public function generateMany(
		int $count,
		string $prompt,
		?Collection $images = null,
	): Collection;
}