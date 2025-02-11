<?php

namespace Mateffy\Magic\Chat\Messages;

use Illuminate\Support\Str;
use Mateffy\Magic\Chat\Prompt\Role;
use Mateffy\Magic\Support\PartialJson;

class JsonMessage implements DataMessage, PartialMessage
{
    use WireableViaArray;
    public function __construct(
        public Role $role,
        public array $data,
        public ?string $partial = null,
    ) {}

    public function toArray(): array
    {
        return [
            'role' => $this->role->value,
            'data' => $this->data,
            'partial' => $this->partial,
        ];
    }

    public static function fromArray(array $data): static
    {
        return new self(
            role: Role::tryFrom($data['role']) ?? Role::Assistant,
            data: $data['data'],
            partial: $data['partial'] ?? null,
        );
    }

    public function append(string $chunk): static
    {
        // If we have a square bracket, we assume it's an array, otherwise we assume it's an object
        if (Str::position($chunk, '[') < Str::position($chunk, '{')) {
            $prefix = '[';
        } else {
            $prefix = '{';
        }

        // This helps if the LLM is outputting some sentences before outputting JSON.
        // Especially older models tend to not be well trained for strict JSON output.
        $this->partial = $this->partial.$chunk;
        $this->data = PartialJson::parse($this->partial) ?? $this->data;

        return $this;
    }

    public function text(): ?string
    {
        return $this->partial ?? json_encode($this->data, JSON_THROW_ON_ERROR);
    }

    public function data(): ?array
    {
        return $this->data;
    }

    public static function fromChunk(string $chunk): static
    {
        return new self(
            role: Role::Assistant,
            data: PartialJson::parse($chunk) ?? [],
            partial: $chunk,
        );
    }

    public function role(): Role
    {
        return $this->role;
    }
}
