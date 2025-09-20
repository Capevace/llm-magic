<?php

namespace Mateffy\Magic\History;

use Illuminate\Support\Str;
use Mateffy\Magic\Chat\MessageCollection;
use Mateffy\Magic\History\Concerns\WithArrayConversion;
use Mateffy\Magic\History\Concerns\WithCollection;

class InMemoryHistory implements MessageHistory
{
	protected string $id;

	use WithArrayConversion;
	use WithCollection;

	public function __construct(
		?string $id = null,
		?MessageCollection $messages = null,
	)
	{
		$this->id = $id ?? Str::uuid()->toString();
		$this->messages = $messages ?? new MessageCollection();
	}

	public function getId(): string
	{
		return $this->id;
	}
}