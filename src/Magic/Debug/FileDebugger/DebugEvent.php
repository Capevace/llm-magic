<?php

namespace Mateffy\Magic\Debug\FileDebugger;

use Illuminate\Support\Str;
use Livewire\Wireable;

class DebugEvent implements Wireable
{
	public string $id;

	public function __construct(
		public string $type,
		public array $data = [],
		?string $id = null
	)
	{
		$this->id = $id ?? Str::ulid();
	}

	public function toLivewire()
	{
		return [
			'type' => $this->type,
			'data' => $this->data,
		];
	}

	public static function fromLivewire($value)
	{
		return new static(
			type: $value['type'],
			data: $value['data'],
		);
	}
}