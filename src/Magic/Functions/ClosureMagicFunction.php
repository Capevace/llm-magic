<?php

namespace Mateffy\Magic\Functions;

use Closure;
use Illuminate\Container\Container;
use Mateffy\Magic\Functions\Concerns\ToolProcessor;
use Mateffy\Magic\LLM\Message\FunctionCall;
use Mateffy\Magic\LLM\Message\FunctionOutputMessage;
use Mateffy\Magic\Loop\EndConversation;
use Mateffy\Magic\Prompt\Role;

abstract class ClosureMagicFunction
{
    public static function make(string $name): MagicFunction
    {
        return app(ToolProcessor::class)->processFunctionTool($name, static::callback());
    }

    abstract public static function callback(): Closure;
}
