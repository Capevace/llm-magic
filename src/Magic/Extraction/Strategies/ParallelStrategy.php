<?php

namespace Mateffy\Magic\Extraction\Strategies;

use App\Models\Actor\ActorTelemetry;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Mateffy\Magic;
use Mateffy\Magic\Chat\Messages\Message;
use Mateffy\Magic\Chat\Messages\TextMessage;
use Mateffy\Magic\Chat\Prompt\ParallelMergerPrompt;
use Mateffy\Magic\Chat\Prompt\Role;
use Mateffy\Magic\Chat\Prompt\SequentialExtractorPrompt;
use Mateffy\Magic\Chat\TokenStats;
use Mateffy\Magic\Extraction\Artifact;
use Mateffy\Magic\Extraction\Extractor;
use Mateffy\Magic\Extraction\SplitArtifact;

class ParallelStrategy extends Extractor
{
    protected ?TokenStats $totalTokenStats = null;

    /**
     * @param  Artifact[]  $artifacts
     */
    public function run(array $artifacts): array
    {
        $maxChunkTokens = 10000;
        $maxBatchTokens = 30000;

        /** @var Collection<SplitArtifact> $chunks */
        $chunks = collect();

        foreach ($artifacts as $artifact) {
            [$splitArtifacts, $tokensUsed] = $artifact->split($maxChunkTokens);

            $chunks = $chunks->concat($splitArtifacts);
        }


        /** @var Collection<Collection<SplitArtifact>> $batches */
        $batches = collect();
        $batch = collect();

        foreach ($chunks as $splitArtifact) {
            $batch->push($splitArtifact);

            if ($batch->sum(fn (SplitArtifact $artifact) => $artifact->tokens) > $maxBatchTokens) {
                $batches->push($batch);
                $batch = collect();
            }
        }

        if ($batch->isNotEmpty()) {
            $batches->push($batch);
        }

        $datas = [];
        $data = null;

        foreach ($batches as $batch) {
            $data = $this->generate($batch, data: $data) ?? $data;

            if ($this->onDataProgress && $data !== null) {
                ($this->onDataProgress)($data);
            }

            $datas[] = $data;
        }

        $data = $this->merge($datas);

        if ($this->onDataProgress && $data !== null) {
            ($this->onDataProgress)($data);
        }

        return $data;
    }

    protected function generate(Collection $artifacts, ?array $data): ?array
    {
        $threadId = Str::uuid()->toString();
        $prompt = new SequentialExtractorPrompt(extractor: $this, artifacts: $artifacts->all(), previousData: $data);

//        if ($this->onActorTelemetry) {
//            ($this->onActorTelemetry)(
//                new ActorTelemetry(
//                    id: $threadId,
//                    model: "{$this->extractor->llm->getOrganization()->id}/{$this->extractor->llm->getModelName()}",
//                    system_prompt: $prompt->system(),
//                )
//            );
//        }

        if ($this->onMessage) {
            ($this->onMessage)(new TextMessage(role: Role::System, content: $prompt->system()), $threadId);

            foreach ($prompt->messages() as $message) {
                ($this->onMessage)($message, $threadId);
            }
        }

        $messages = [];
        $lastTokenStats = null;

        $messages = Magic::chat()
            ->model($this->llm)
            ->prompt($prompt)
            ->onMessageProgress(function (Message $message) use (&$messages, $threadId) {
                //            ($this->onDataProgress)(new InferenceResult(value: $message->toArray(), messages: $messages));

                if ($this->onMessageProgress) {
                    ($this->onMessageProgress)($message, $threadId);
                }
            })
            ->onMessage(function (Message $message) use (&$messages, $threadId) {
                $messages[] = $message;

                //            ($this->onDataProgress)(new InferenceResult(value: $message->toArray(), messages: $messages));

                if ($this->onMessage) {
                    ($this->onMessage)($message, $threadId);
                }
            })
            ->onTokenStats(function (TokenStats $tokenStats) use (&$data, &$lastTokenStats) {
                $lastTokenStats = $tokenStats;

                if ($this->onTokenStats) {
                    ($this->onTokenStats)($this->totalTokenStats?->add($lastTokenStats) ?? $lastTokenStats);
                }
            })
            ->stream();

        $this->totalTokenStats = $this->totalTokenStats?->add($lastTokenStats) ?? $lastTokenStats;

        return $messages->lastData();
    }

    protected function merge(array $datas): ?array
    {
        $threadId = Str::uuid();
        $prompt = new ParallelMergerPrompt(extractor: $this, datas: $datas);

//        if ($this->onActorTelemetry) {
//            ($this->onActorTelemetry)(new ActorTelemetry(
//                id: $threadId,
//                model: "{$this->extractor->llm->getOrganization()->id}/{$this->extractor->llm->getModelName()}",
//                system_prompt: $prompt->system(),
//            ));
//        }

        if ($this->onMessage) {
            ($this->onMessage)(new TextMessage(role: Role::System, content: $prompt->system()), $threadId);

            foreach ($prompt->messages() as $message) {
                ($this->onMessage)($message, $threadId);
            }
        }

        $lastTokenStats = null;
        $strategy = $this;

        $messages = Magic::chat()
            ->model($this->llm)
            ->prompt($prompt)
            ->onMessageProgress(function (Message $message) use (&$messages, $threadId, $strategy) {
                if ($strategy->onDataProgress && $data = $message->toArray()['data'] ?? null) {
                    ($strategy->onDataProgress)($data, $threadId);
                }

                if ($strategy->onMessageProgress) {
                    ($strategy->onMessageProgress)($message, $threadId);
                }
            })
            ->onMessage(function (Message $message) use (&$messages, $threadId, $strategy) {
                if ($strategy->onDataProgress && $data = $message->toArray()['data'] ?? null) {
                    ($strategy->onDataProgress)($data, $threadId);
                }

                if ($strategy->onMessage) {
                    ($strategy->onMessage)($message, $threadId);
                }
            })
            ->onTokenStats(function (TokenStats $tokenStats) use (&$data, &$lastTokenStats, $strategy) {
                $lastTokenStats = $tokenStats;

                if ($strategy->onTokenStats) {
                    ($strategy->onTokenStats)($strategy->totalTokenStats?->add($lastTokenStats) ?? $lastTokenStats);
                }
            })
            ->stream();

        if ($lastTokenStats) {
            $this->totalTokenStats = $this->totalTokenStats?->add($lastTokenStats) ?? $lastTokenStats;
        }

        return $messages->lastData();
    }
}
