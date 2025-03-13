<?php

namespace Mateffy\Magic\Tools\Prebuilt;

use Mateffy\Magic\Chat\Messages\ToolCall;
use Mateffy\Magic\Tools\InvokableTool;

class OutputJsonSchema implements InvokableTool
{
    public function __construct() {}

    public function name(): string
    {
        return 'outputSchema';
    }

    public function description(): ?string
    {
        return 'Output the JSON Schema for the given instructions. Important: The schema MUST be contained within a JSON object. This means, the schema MUST always be an object that then contains the requested contents. A schema may NEVER be anything else at the root level. Not arrays, not strings, not numbers, not booleans, not null. Only objects.';
    }

    public function schema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'schema' => [
                    'type' => 'object',
                    'description' => 'The JSON Schema to output',
                    'properties' => [
                        'type' => [
                            'type' => 'string',
                            'description' => 'The type of the schema',
                            'enum' => [
                                'object',
                            ],
                        ],
                        'required' => [
                            'type' => 'array',
                            'description' => 'The required properties of the schema',
							'items' => [
								'type' => 'string',
							]
                        ],
                        'properties' => [
                            'type' => 'object',
                            'description' => 'The properties of the schema',
							'additionalProperties' => true,
                        ],
                    ],
                    'required' => ['type', 'properties', 'required'],
                ],
            ],
            'required' => ['schema'],
        ];
    }

    public function validate(array $arguments): array
    {
        return $arguments;
    }

    public function execute(ToolCall $call): mixed
    {
        return null;
    }

    public function callback(): \Closure
    {
        return fn () => null;
    }
}
