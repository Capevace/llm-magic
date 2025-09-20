<?php

namespace Mateffy\Magic\History;

class DatabaseHistory
{
	protected string $id;
	protected string $threadModel;
	protected string $messageModel;

	public function __construct(
		?string $id = null,
		?string $threadModel = null,
		?string $messageModel = null,
	)
	{

	}

	public function getId(): string
	{
		return $this->id;
	}
}