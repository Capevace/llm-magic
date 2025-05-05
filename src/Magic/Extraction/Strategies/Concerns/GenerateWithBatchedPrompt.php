<?php

namespace Mateffy\Magic\Extraction\Strategies\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Mateffy\Magic;
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

        $data = $this->send(
			threadId: $threadId,
			llm: $this->llm,
			prompt: $prompt,
			logDataProgress: false
		);

		if ($data === null) {
			Log::warning('No data received from LLM for batch.', [
				'threadId' => $threadId,
				'context' => $this->contextOptions->getEvaluationTypeLabel(),
				'llm' => $this->llm->getModelName(),
			]);
		}

        $solution = Magic::chat()
            ->prompt('What is the result of ((1000 - 900) + 26) / 3?') ->tools([
            'add' => fn (float $a, float $b) => $a + $b, 'subtract' => fn (float $a, float $b) => $a - $b, 'multiply' => fn (float $a, float $b) => $a * $b, 'divide' => fn (float $a, float $b) => $a / $b,
            ]) ->toolChoice(ToolChoice::Required) ->send()
            ->lastText();
            // -> "The result is 42"

		return $data;
    }
}


