<?php

namespace Mateffy\Magic\Extraction\Strategies\Concerns;

use Illuminate\Support\Collection;
use Mateffy\Magic\Chat\Prompt\SequentialExtractorPrompt;

trait GenerateWithBatchedPrompt
{
	protected function generate(Collection $artifacts, ?array $data = null): ?array
    {
        $prompt = new SequentialExtractorPrompt(
			extractor: $this,
			artifacts: $artifacts->all(),
			contextOptions: $this->contextOptions,
			previousData: $data
		);

		$threadId = $this->createActorThread(llm: $this->llm, prompt: $prompt);

        return $this->send(
			threadId: $threadId,
			llm: $this->llm,
			prompt: $prompt,
			logDataProgress: false
		);
    }
}