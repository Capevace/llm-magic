<?php

namespace Mateffy\Magic\History\Concerns;

use Closure;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Mateffy\Magic\Chat\MessageCollection;
use Mateffy\Magic\Chat\Messages\Message;

trait WithCollection
{
	protected MessageCollection $messages;

	protected function ensureMessages(): void
	{
		if (isset($this->messages)) {
			return;
		}

		$this->refresh();
	}

	public function push(Message $message): static
	{
		$this->ensureMessages();

		$this->messages->push($message);
		$this->save();

		return $this;
	}

	public function pushMany(array|Collection $message): static
	{
		$this->ensureMessages();

		if ($message instanceof Collection) {
			$message = $message->all();
		}

		foreach ($message as $msg) {
			if (!$msg instanceof Message) {
				throw new InvalidArgumentException("Message could not be converted. Message is not an instance of " . Message::class);
			}
		}

		$this->messages->push(...$message);
		$this->save();

		return $this;
	}

	public function prepend(Message $message): static
	{
		$this->ensureMessages();

		$this->messages->unshift($message);
		$this->save();

		return $this;
	}

	public function shift(): ?Message
	{
		$this->ensureMessages();

		$message = $this->messages->shift();

		if ($message) {
			$this->save();
		}

		return $message;
	}

	public function pop(): ?Message
	{
		$this->ensureMessages();

		$message = $this->messages->pop();

		if ($message) {
			$this->save();
		}

		return $message;
	}

	public function get(int $index): ?Message
	{
		$this->ensureMessages();

		if ($index < 0 || $index >= $this->messages->count()) {
			return null;
		}

		return $this->messages->get($index);
	}

	public function find(Closure $callback): ?Message
	{
		$this->ensureMessages();

		$index = $this->messages->search($callback);

		if ($index === false) {
			return null;
		}

		return $this->messages->get($index);
	}

	public function all(): MessageCollection
	{
		$this->ensureMessages();

		return $this->messages;
	}

	public function save(): static
	{
		$this->ensureMessages();

		// Save the messages to the storage
		// This is a placeholder for the actual save logic
		// You can implement your own logic here

		return $this;
	}

	public function refresh(): static
	{
		$this->ensureMessages();

		return $this;
	}

	public function clear(): static
	{
		$this->messages = new MessageCollection();
		$this->save();

		return $this;
	}
}