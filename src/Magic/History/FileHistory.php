<?php

namespace Mateffy\Magic\History;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use JsonException;
use Mateffy\Magic\Chat\MessageCollection;
use Mateffy\Magic\Chat\Messages\Message;
use Mateffy\Magic\Exceptions\CouldNotLoadHistory;
use Mateffy\Magic\Exceptions\CouldNotSaveHistory;
use Mateffy\Magic\History\Concerns\WithArrayConversion;
use Mateffy\Magic\History\Concerns\WithCollection;

class FileHistory implements MessageHistory
{
	use WithArrayConversion;
	use WithCollection;

	protected string $id;
	protected string $disk;
	protected ?string $prefix;
	protected ?string $path;
	protected MessageCollection $messages;

	public function __construct(
		?string $id = null,
		?string $disk = null,
		?string $prefix = null,
		?string $path = null,
		?MessageCollection $messages = null,
	)
	{
		$this->id = $id ?? Str::uuid()->toString();
		$this->disk = $disk ?? config('llm-magic.chat_history.file.disk');
		$this->prefix = $prefix ?? config('llm-magic.chat_history.file.prefix');
		$this->path = $path ?? config('llm-magic.chat_history.file.path');

		if ($messages) {
			$this->messages = $messages;
		}
	}

	public function getId(): string
	{
		return $this->id;
	}

	protected function getFilepath(): string
	{
		$path = rtrim($this->path ?? '', '/');

		$prefix = $this->prefix ?? '';
		$filename = "{$prefix}{$this->id}.json";

		if (empty($path)) {
			return $filename;
		}

		return "{$path}/{$filename}";
	}

	protected function getDisk(): Filesystem
	{
		return Storage::disk($this->disk);
	}

	/**
	 * @throws CouldNotLoadHistory
	 */
	public function refresh(): static
	{
		$this->messages = new MessageCollection();
		$disk = $this->getDisk();

		try {
			$messages_json = $disk->get($this->getFilepath());
			$messages = json_decode($messages_json, true, flags: JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		} catch (FileNotFoundException $e) {
			throw new CouldNotLoadHistory(
				"Failed to load history from disk: {$this->disk}, path: {$this->getFilepath()}",
				previous: $e
			);
		} catch (JsonException $e) {
			throw new CouldNotLoadHistory(
				"Failed to JSON decode history from disk: {$this->disk}, path: {$this->getFilepath()}",
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

	/**
	 * @throws CouldNotSaveHistory
	 */
	public function save(): static
	{
		$this->ensureMessages();

		$disk = $this->getDisk();

		$messages = $this->messages->map(fn (Message $message) => $this->convertMessageToArray($message))->toArray();

		try {
			$messages_json = json_encode($messages, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		} catch (JsonException $e) {
			throw new CouldNotSaveHistory(
				"Failed to save history to disk: {$this->disk}, path: {$this->getFilepath()}",
				previous: $e
			);
		}

		$disk->put($this->getFilepath(), $messages_json);

		return $this;
	}

	public function clear(): static
	{
		$this->messages = new MessageCollection();

		$success = $this->getDisk()->delete($this->getFilepath());

		if (!$success) {
			$error = error_get_last();

			throw new CouldNotSaveHistory("Failed to clear history on disk: {$this->disk}, path: {$this->getFilepath()}, error: " . json_encode($error));
		}

		return $this;
	}
}