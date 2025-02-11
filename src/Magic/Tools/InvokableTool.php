<?php

namespace Mateffy\Magic\Tools;

use Mateffy\Magic\Chat\Messages\FunctionCall;

interface InvokableTool
{
    public function name(): string;

    public function schema(): array;

    public function validate(array $arguments): array;

    public function execute(FunctionCall $call): mixed;
}
