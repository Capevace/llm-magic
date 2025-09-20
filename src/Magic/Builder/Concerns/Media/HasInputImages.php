<?php

namespace Mateffy\Magic\Builder\Concerns\Media;

use Closure;
use Illuminate\Support\Collection;

trait HasInputImages
{
	protected Closure|array|Collection|null $inputImages = null;

	public function images(Closure|array|Collection|null $inputImages): static
	{
		$this->inputImages = $inputImages;

		return $this;
	}

	public function getInputImages(): Collection
	{
		return Collection::wrap($this->evaluate($this->inputImages) ?? new Collection());
	}
}
