<?php

namespace Mateffy\Magic\Extraction\Strategies;

use Illuminate\Support\Str;
use Mateffy\Magic\Chat\Messages\Message;
use Mateffy\Magic\Chat\Prompt\ExtractorPrompt;
use Mateffy\Magic\Extraction\Artifact;
use Mateffy\Magic\Extraction\Extractor;

class SimpleStrategy extends Extractor
{
    /**
     * @param  Artifact[]  $artifacts
     */
    public function run(array $artifacts): array
    {
        $threadId = Str::uuid();
        $prompt = new ExtractorPrompt(extractor: $this, artifacts: $artifacts);

//        if ($this->onActorTelemetry) {
//            ($this->onActorTelemetry)(new ActorTelemetry(
//                id: $threadId,
//                model: "{$this->extractor->llm->getOrganization()->id}/{$this->extractor->llm->getModelName()}",
//                system_prompt: $prompt->system(),
//            ));
//        }

        $messages = [];

        $onMessageProgress = function (Message $message) use (&$messages) {
            if ($this->onDataProgress && $data = $message->toArray()['data'] ?? null) {
                ($this->onDataProgress)($data);
            }

            if ($this->onMessageProgress) {
                ($this->onMessageProgress)($message);
            }
        };

        $onMessage = function (Message $message) use (&$messages, $threadId) {
            $messages[] = $message;

            if ($this->onDataProgress && $data = $message->toArray()['data'] ?? null) {
                ($this->onDataProgress)($data);
            }

            if ($this->onMessage) {
                ($this->onMessage)($message, $threadId);
            }
        };

        $responses = $this->llm->stream(
            prompt: $prompt,
            onMessageProgress: $onMessageProgress,
            onMessage: $onMessage,
            onTokenStats: $this->onTokenStats
        );

        return $responses->lastData();
    }
}
