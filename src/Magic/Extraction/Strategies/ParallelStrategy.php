<?php

namespace Mateffy\Magic\Extraction\Strategies;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Mateffy\Magic\Chat\Prompt\ParallelMergerPrompt;
use Mateffy\Magic\Chat\Prompt\SequentialExtractorPrompt;
use Mateffy\Magic\Extraction\ArtifactBatcher;
use Mateffy\Magic\Extraction\Artifacts\Artifact;

class ParallelStrategy extends Extractor
{
    /**
     * @param Artifact[] $artifacts
     */
    public function run(array $artifacts): array
    {
		$batches = ArtifactBatcher::batch(
			artifacts: $artifacts,
			options: $this->contextOptions,
			maxTokens: $this->chunkSize,
			llm: $this->llm
		);

        $dataList = [];
        $data = null;

        foreach ($batches as $batch) {
            $data = $this->generate($batch, data: $data) ?? $data;

            $dataList[] = $data;
        }

        $data = $this->merge($dataList);

        $this->logDataProgress(data: $data);

        return $data;
    }

    protected function generate(Collection $artifacts, ?array $data): ?array
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
			prompt: $prompt
		);
    }

    protected function merge(array $dataList): ?array
    {
        $prompt = new ParallelMergerPrompt(extractor: $this, datas: $dataList);

        $threadId = $this->createActorThread(llm: $this->llm, prompt: $prompt);

		return $this->send(
			threadId: $threadId,
			llm: $this->llm,
			prompt: $prompt
		);
    }

	public static function getLabel(): string
	{
		return __('Parallel');
	}
}
