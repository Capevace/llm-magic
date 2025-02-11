<?php

namespace Mateffy\Magic\Chat;

use Closure;
use Illuminate\Support\Collection;
use Mateffy\Magic\Chat\Messages\DataMessage;
use Mateffy\Magic\Chat\Messages\FunctionInvocationMessage;
use Mateffy\Magic\Chat\Messages\FunctionOutputMessage;
use Mateffy\Magic\Chat\Messages\Message;
use Mateffy\Magic\Chat\Messages\TextMessage;

class MessageCollection extends Collection
{
    public function firstTextMessage(): ?TextMessage
    {
        return $this->first(fn (Message $message) => $message instanceof TextMessage);
    }

    public function firstText(): ?string
    {
        return $this->firstTextMessage()?->text();
    }

    public function firstDataMessage(): ?DataMessage
    {
        return $this->first(fn (Message $message) => $message instanceof DataMessage);
    }

    public function firstData(): ?array
    {
        return $this->firstDataMessage()?->data();
    }

    public function firstFunctionInvokation(?Closure $filter = null): ?FunctionInvocationMessage
    {
        return $this->first(fn (Message $message) => $message instanceof FunctionInvocationMessage && ($filter === null || $filter($message)));
    }

    public function firstFunctionOutput(?Closure $filter = null): ?FunctionOutputMessage
    {
        return $this->first(fn (Message $message) => $message instanceof FunctionOutputMessage && ($filter === null || $filter($message)));
    }

    public function lastTextMessage(): ?TextMessage
    {
        return $this->last(fn (Message $message) => $message instanceof TextMessage);
    }

    public function lastText(): ?string
    {
        return $this->lastTextMessage()?->text();
    }

    /**
     * @return DataMessage[]|null
     */
    public function lastDataMessage(): ?DataMessage
    {
        return $this->last(fn (Message $message) => $message instanceof DataMessage);
    }

    /**
     * @return array|null
     */
    public function lastData(): ?array
    {
        return $this->lastDataMessage()?->data();
    }

    public function lastFunctionInvokation(?Closure $filter = null): ?FunctionInvocationMessage
    {
        return $this->last(fn (Message $message) => $message instanceof FunctionInvocationMessage && ($filter === null || $filter($message)));
    }

    public function lastFunctionOutput(?Closure $filter = null): ?FunctionOutputMessage
    {
        return $this->last(fn (Message $message) => $message instanceof FunctionOutputMessage && ($filter === null || $filter($message)));
    }

    public function formattedText(): string
    {
        $lastIndex = $this->count() - 1;
        $lineBreak = fn (int $index) => $index === $lastIndex ? '' : "\n";

        return $this
            ->map(fn (Message $message, int $index) => match ($message::class) {
                FunctionInvocationMessage::class => "Tool: " . trim($message->text()),
                FunctionOutputMessage::class => "Output: " . trim($message->text()) . $lineBreak($index),
                default => trim($message->text()) . $lineBreak($index),
            })
            ->join("\n");
    }
}
