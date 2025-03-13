<?php

namespace Mateffy\Magic\Tools\Prebuilt;


use Mateffy\Magic;
use Mateffy\Magic\Chat\Messages\ToolCall;
use Mateffy\Magic\Tools\InvokableTool;

class MagicReturnTool implements InvokableTool
{
    public function __construct(
        protected string $type,
        protected ?array $schema = null,
    )
    {
    }

    public function name(): string
    {
        return 'returnValue';
    }

    public function schema(): array
    {
        if ($this->type === 'object') {
            $property = $this->schema;
        } else if ($this->type === 'array') {
            $property = [
                'type' => 'array',
                'items' => $this->schema ?? [
                    'type' => 'string',
                ],
            ];
        } else {
            $property = [
                'type' => $this->type,
                'description' => 'The value to return',
                ...($this->schema ?? []),
            ];
        }

        return [
            'type' => 'object',
            'description' => 'Return a value of the given type',
            'required' => ['value'],
            'properties' => [
                'value' => $property,
            ],
        ];
    }

    public function validate(array $arguments): array
    {
        // TODO: Implement JSON schema validation

        return $arguments;
    }

    public function execute(ToolCall $call): mixed
    {
        return Magic::end(match ($this->type) {
            'number' => floatval($call->arguments['value']),
            'integer' => intval($call->arguments['value']),
            'boolean' => boolval($call->arguments['value']),
            default => $call->arguments['value'],
        });
    }
}
