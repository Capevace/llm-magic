<?php

namespace Mateffy\Magic\Support;

use Closure;
use Illuminate\Container\Container;

trait EvaluatesClosures
{
	public function evaluate(mixed $value): mixed
	{
		if (is_callable($value)) {
			// Dynamic injection using Laravel Container
			return Container::getInstance()->call($value);
		}

		return $value;
	}
}