<?php

namespace Mateffy\Magic\History;

use Closure;
use Illuminate\Support\Collection;
use Mateffy\Magic\Chat\MessageCollection;
use Mateffy\Magic\Chat\Messages\Message;

interface MessageHistory
{
	public const string IN_MEMORY = 'in_memory';
	public const string FILE = 'file';
	public const string CACHE = 'cache';
	public const string DATABASE = 'database';

	public function push(Message $message): static;
	public function pushMany(array|Collection $message): static;
	public function prepend(Message $message): static;
	public function shift(): ?Message;
	public function pop(): ?Message;
	public function get(int $index): ?Message;
	public function find(Closure $callback): ?Message;

	public function getId(): string;
	public function save(): static;
	public function refresh(): static;
	public function clear(): static;
	public function all(): MessageCollection;
}