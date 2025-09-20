<?php

namespace Mateffy\Magic\Builder\Concerns\Media;

use Closure;

trait HasMediaPrompt
{
	protected Closure|string|null $prompt = null;

	public function prompt(Closure|string|null $prompt): static
	{
		$this->prompt = $prompt;

		return $this;
	}
}