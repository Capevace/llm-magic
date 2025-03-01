<?php

namespace Mateffy\Magic\Builder;

use Illuminate\Support\Collection;
use Mateffy\Magic\Builder\Concerns\HasArtifacts;
use Mateffy\Magic\Builder\Concerns\HasContextOptions;
use Mateffy\Magic\Builder\Concerns\HasExtractionModelCallbacks;
use Mateffy\Magic\Builder\Concerns\HasMessageCallbacks;
use Mateffy\Magic\Builder\Concerns\HasModel;
use Mateffy\Magic\Builder\Concerns\HasSchema;
use Mateffy\Magic\Builder\Concerns\HasStrategy;
use Mateffy\Magic\Builder\Concerns\HasSystemPrompt;
use Mateffy\Magic\Builder\Concerns\HasTokenCallback;
use Mateffy\Magic\Builder\Concerns\HasTools;
use Mateffy\Magic\Chat\MessageCollection;
use Mateffy\Magic\Extraction\Extractor;
use Mateffy\Magic\Extraction\Strategy;

class ExtractionLLMBuilder
{
    use HasArtifacts;
	use HasContextOptions;
    use HasExtractionModelCallbacks;
    use HasModel;
    use HasMessageCallbacks;
    use HasSchema;
    use HasStrategy;
    use HasSystemPrompt;
    use HasTokenCallback;
    use HasTools;

    public function stream(): Collection
    {
        $strategyClass = $this->getStrategyClass();

        /** @var Strategy $strategy */
        $strategy = $strategyClass::make(
            llm: $this->model,
			contextOptions: $this->getContextOptions(),
			outputInstructions: 'Output instructions',
			schema: $this->schema,
			onDataProgress: $this->onDataProgress,
			onTokenStats: $this->onTokenStats,
			onMessageProgress: $this->onMessageProgress,
			onMessage: $this->onMessage,
			onActorTelemetry: $this->onActorTelemetry,
        );

        $result = $strategy->run($this->artifacts);

        return collect($result);
    }

    public function send(): Collection
    {
        return collect();
    }
}
