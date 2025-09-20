<?php

namespace Mateffy\Magic\History\Concerns;

use InvalidArgumentException;
use Mateffy\Magic\Chat\Messages\Message;

trait WithArrayConversion
{
	protected function convertMessageToArray(Message $data): array
	{
		return [
			'class' => $data::class,
			'data' => $data->toArray(),
		];
	}

	protected function convertArrayToMessage(array $message): Message
	{
		['class' => $class, 'data' => $data] = $message;

		if (!class_exists($class)) {
			throw new InvalidArgumentException("Message could not be converted. Class {$class} does not exist.");
		}

		if (!is_subclass_of($class, Message::class)) {
			throw new InvalidArgumentException("Message could not be converted. Class {$class} is not a subclass of " . Message::class);
		}

		return $class::fromArray($data);
	}
}