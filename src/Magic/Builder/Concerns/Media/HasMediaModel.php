<?php

namespace Mateffy\Magic\Builder\Concerns\Media;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Mateffy\Magic;
use Mateffy\Magic\Models\Image\OpenAI;
use Mateffy\Magic\Models\ImageModel;

trait HasMediaModel
{
	protected Closure|ImageModel|string|null $model = null;

	public function model(Closure|ImageModel|string|null $model): static
	{
		$this->model = $model;

		return $this;
	}

	public function getImageModel(): ImageModel
	{
		$model = $this->evaluate($this->model);
        
        if (is_null($model)) {
            $model = Magic::defaultImageModelName();
        }

		if (is_string($model)) {
			$org = Str::before($model, '/');
			$modelName = Str::after($model, '/');

			$model = match ($org) {
				'openai' => Container::getInstance()->make(OpenAI::class, [
					'model' => $modelName,
				]),
				default => throw new InvalidArgumentException(
					"Invalid model: {$model}. Supported models: openai/...",
				),
			};
		}

		return $model;
	}
}