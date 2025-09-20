<?php

namespace Mateffy\Magic\History;

use Closure;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Mateffy\Magic\Chat\MessageCollection;
use Mateffy\Magic\Chat\Messages\Message;
use Mateffy\Magic\Exceptions\CouldNotLoadHistory;
use Mateffy\Magic\History\Concerns\WithArrayConversion;
use Mateffy\Magic\History\Concerns\WithCollection;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class CacheHistory implements MessageHistory
{
	use WithArrayConversion;
	use WithCollection;

	protected string $id;
	protected CacheManager $cache;
	protected string $prefix;
	protected int $duration;

	public function __construct(
		?string $id = null,
		?string $prefix = null,
		?int $duration = null,
		?CacheManager $cache = null,
		?MessageCollection $messages = null,
	)
	{
		$this->id = $id ?? Str::uuid()->toString();
		$this->prefix = $prefix ?? config('llm-magic.chat_history.cache.prefix');
		$this->duration = $duration ?? config('llm-magic.chat_history.cache.duration');
		$this->cache = $cache ?? cache();

		if ($messages) {
			$this->messages = $messages;
		}
	}

	public function getId(): string
	{
		return $this->id;
	}

	public function getCacheKey(): string
	{
		return "{$this->prefix}.{$this->id}";
	}

	public function refresh(): static
	{
		$this->messages = new MessageCollection();

		try {
			$messages = $this->cache->get($this->getCacheKey(), []);
		} catch (ContainerExceptionInterface|NotFoundExceptionInterface $e) {
			throw new CouldNotLoadHistory(
				message: "Could not load history from cache. Cache key: {$this->getCacheKey()}",
				previous: $e
			);
		}

		foreach ($messages as $message) {
			if (!is_array($message)) {
				throw new InvalidArgumentException("Message could not be converted. Message is not an array.");
			}

			$this->messages->push($this->convertArrayToMessage($message));
		}

		return $this;
	}

	public function save(): static
	{
		$this->ensureMessages();

		$success = $this->cache->put(
			$this->getCacheKey(),
			$this->messages->map(fn(Message $message) => $this->convertMessageToArray($message))->all(),
			$this->duration
		);

		if (!$success) {
			$error = error_get_last();

			throw new CouldNotLoadHistory(
				message: "Could not save history to cache. Cache key: {$this->getCacheKey()}. Error: " . json_encode($error),
			);
		}

		return $this;
	}

	public function clear(): static
	{
		$this->messages = new MessageCollection();
		$this->cache->forget($this->getCacheKey());

		return $this;
	}
}