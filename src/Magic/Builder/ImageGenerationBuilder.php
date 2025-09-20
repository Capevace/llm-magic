<?php

namespace Mateffy\Magic\Builder;

use Closure;
use Illuminate\Support\Collection;
use Mateffy\Magic\Builder\Concerns\Media\HasInputImages;
use Mateffy\Magic\Builder\Concerns\Media\HasMediaModel;
use Mateffy\Magic\Builder\Concerns\Media\HasMediaPrompt;
use Mateffy\Magic\Media\Image;
use Mateffy\Magic\Support\EvaluatesClosures;

class ImageGenerationBuilder
{
	use EvaluatesClosures;
	use HasInputImages;
	use HasMediaModel;
	use HasMediaPrompt;

	public function generate(): Image
	{
		$prompt = $this->evaluate($this->prompt);
		$images = $this->getInputImages();
		$model = $this->getImageModel();

		return $model->generate($prompt, $images);
	}

	/**
	 * @param int $count
	 * @return Collection<Image>
	 */
	public function generateMany(int $count): Collection
	{
		$prompt = $this->evaluate($this->prompt);
		$images = $this->getInputImages();
		$model = $this->getImageModel();

		return $model->generateMany($count, $prompt, $images);
	}
}