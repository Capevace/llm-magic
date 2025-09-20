<?php

namespace Mateffy\Magic\Builder\Concerns;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Mateffy\Magic\Chat\Messages\Message;
use Mateffy\Magic\History\CacheHistory;
use Mateffy\Magic\History\FileHistory;
use Mateffy\Magic\History\InMemoryHistory;
use Mateffy\Magic\History\MessageHistory;

trait HasMessages
{
	protected MessageHistory $history;
	protected ?string $historyId = null;

	public function history(MessageHistory|string $history): static
	{
		if (is_string($history)) {
			$this->history = $this->initializeStringHistory($history);
		} else {
			$this->history = $history;
		}

		return $this;
	}

	public function getHistory(): MessageHistory
	{
		if (!isset($this->history)) {
			$defaultHistory = config('llm-magic.chat_history.default', MessageHistory::IN_MEMORY);

			$this->history = $this->initializeStringHistory($defaultHistory);
		}

		return $this->history;
	}

	protected function initializeStringHistory(string $history)
	{
		if (class_exists($history)) {
			return app($history);
		} else {
			return match ($history) {
				MessageHistory::IN_MEMORY => app(InMemoryHistory::class, ['id' => $this->historyId]),
				MessageHistory::FILE => app(FileHistory::class, ['id' => $this->historyId]),
				MessageHistory::CACHE => app(CacheHistory::class, ['id' => $this->historyId]),
				MessageHistory::DATABASE => app(DatabaseHistory::class, ['id' => $this->historyId]),
				default => throw new InvalidArgumentException("Invalid history type: {$history}"),
			};
		}
	}

	public function clearMessages(): static
	{
		$this->getHistory()->clear();

		return $this;
	}

    /**
     * @param Message[] $messages
     */
    public function messages(array|Collection $messages): static
    {
		$this->clearMessages();
		$this->addMessages($messages);

		return $this;
    }

    public function addMessage(Message $message): static
    {
		$this->getHistory()->push($message);

		return $this;
    }

    /**
     * @param  Message[]  $messages
     */
    public function addMessages(array $messages): static
    {
		$this->getHistory()->pushMany($messages);

        return $this;
    }
}
