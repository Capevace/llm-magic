<?php

namespace Mateffy\Magic\Tools\Prebuilt;

use Closure;
use Mateffy\Magic\Chat\Messages\ToolCall;
use Mateffy\Magic\Tools\InvokableTool;

class Multiply implements InvokableTool
{
    public function name(): string
    {
        return 'multiply';
    }

    public function schema(): array
    {
        return [
            'name' => 'multiply',
            'description' => 'Multiply two numbers',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'a' => [
                        'type' => 'number',
                        'description' => 'The first number to multiply',
                    ],
                    'b' => [
                        'type' => 'number',
                        'description' => 'The second number to multiply',
                    ],
                ],
                'required' => ['a', 'b'],
            ],
            'returns' => [
                'type' => 'number',
                'description' => 'The result of multiplying the two numbers',
            ],
        ];
    }

    public function validate(array $arguments): array
    {
        $validator = validator($arguments, [
            'a' => 'required|numeric',
            'b' => 'required|numeric',
        ]);

        $validator->validate();

        return $validator->validated();
    }

    public function execute(ToolCall $call): mixed
    {
        return $this->callback()($call->arguments['a'], $call->arguments['b']);
    }



    public function callback(): Closure
    {
        return function (int|float $a, int|float $b): int|float {
            return $a * $b;
        };
    }
}
