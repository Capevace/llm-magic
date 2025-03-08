<?php

namespace Mateffy\Magic\Extraction\Strategies;

use Illuminate\Support\Collection;
use Mateffy\Magic\Chat\Prompt\SequentialExtractorPrompt;
use Mateffy\Magic\Extraction\ArtifactBatcher;
use Mateffy\Magic\Extraction\Artifacts\Artifact;

class SequentialStrategy extends Extractor
{
    /**
     * @param  Artifact[]  $artifacts
     */
    public function run(array $artifacts): array
    {
        $batches = ArtifactBatcher::batch(
			artifacts: $artifacts,
			options: $this->contextOptions,
			maxTokens: $this->chunkSize,
			llm: $this->llm
		);

        $data = null;

        foreach ($batches as $batch) {
            $data = $this->generate($batch, $data);
        }

		$this->logDataProgress(data: $data);

        return $data;
    }

    protected function generate(Collection $artifacts, ?array $previousData): array
    {
        $prompt = new SequentialExtractorPrompt(
			extractor: $this,
			artifacts: $artifacts->all(), contextOptions: $this->contextOptions,
			previousData: $previousData,
		);

        $threadId = $this->createActorThread(llm: $this->llm, prompt: $prompt);

        $data = $this->send(
			threadId: $threadId,
			llm: $this->llm,
			prompt: $prompt
		);

		return $data ?? $previousData;
    }

	public static function getLabel(): string
	{
		return __('Sequential');
	}
}
