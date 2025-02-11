<?php

namespace Mateffy\Magic\Extraction\Strategies;

use Closure;
use Illuminate\Support\Collection;
use Mateffy\Magic\Chat\Messages\DataMessage;
use Mateffy\Magic\Chat\Messages\Message;
use Mateffy\Magic\Chat\Prompt\SequentialExtractorPrompt;
use Mateffy\Magic\Chat\TokenStats;
use Mateffy\Magic\Extraction\Artifact;
use Mateffy\Magic\Extraction\Extractor;
use Mateffy\Magic\Loop\InferenceResult;

class SequentialStrategy extends Extractor
{
    protected ?TokenStats $totalTokenStats = null;

    /**
     * @param  Artifact[]  $artifacts
     */
    public function run(array $artifacts): array
    {
        $maxTokens = 50000;

        /** @var Collection<Collection<Artifact>> $batches */
        $batches = collect();

        /** @var Collection<Artifact> $batch */
        $batch = collect();
        $batchTokens = 0;

        foreach ($artifacts as $artifact) {
            [$splitArtifacts, $tokensUsed] = $artifact->split($maxTokens);

            while (count($splitArtifacts) > 0) {
                $artifact = array_shift($splitArtifacts);

                if ($batchTokens + $artifact->tokens > $maxTokens) {
                    $batches->push($batch);
                    $batch = collect();
                    $batchTokens = 0;
                }

                $batch->push($artifact);
                $batchTokens += $artifact->tokens;
            }
        }

        if ($batch->isNotEmpty()) {
            $batches->push($batch);
        }

        $data = null;

        foreach ($batches as $batch) {
            $data = $this->generate($batch, $data);

            if ($this->onDataProgress && $data !== null) {
                ($this->onDataProgress)($data);
            }
        }

        return $data;
    }

    protected function generate(Collection $artifacts, ?array $data): array
    {
        $prompt = new SequentialExtractorPrompt(extractor: $this, artifacts: $artifacts->all(), previousData: $data);

//        if ($this->onActorTelemetry) {
//            ($this->onActorTelemetry)(new ActorTelemetry(
//                model: "{$this->extractor->llm->getOrganization()->id}/{$this->extractor->llm->getModelName()}",
//                system_prompt: $prompt->system(),
//            ));
//        }

        $messages = [];

        $onMessageProgress = function (Message $message) use (&$messages) {
            if ($this->onMessageProgress) {
                ($this->onMessageProgress)($message);
            }
        };

        $onMessage = function (Message $message) use (&$messages) {
            $messages[] = $message;

            if ($this->onMessage) {
                ($this->onMessage)($message);
            }
        };

        $result = $this->llm->stream(
            prompt: $prompt,
            onMessageProgress: $onMessageProgress,
            onMessage: $onMessage,
            onTokenStats: function (TokenStats $tokenStats) use (&$data) {
                $this->totalTokenStats = $this->totalTokenStats?->add($tokenStats) ?? $tokenStats;

                if ($this->onTokenStats) {
                    ($this->onTokenStats)($this->totalTokenStats);
                }
            }
        );

        return $result->firstData() ?? $data;
    }
}
