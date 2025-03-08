<?php

namespace Mateffy\Magic\Extraction\Strategies;

use Mateffy\Magic\Chat\Prompt\ExtractorPrompt;
use Mateffy\Magic\Extraction\Artifacts\Artifact;

class SimpleStrategy extends Extractor
{
	/**
	 * @param Artifact[] $artifacts
	 */
    public function run(array $artifacts): array
    {
        $prompt = new ExtractorPrompt(extractor: $this, artifacts: $artifacts, contextOptions: $this->contextOptions);

        $threadId = $this->createActorThread(llm: $this->llm, prompt: $prompt);

        return $this->send(threadId: $threadId, llm: $this->llm, prompt: $prompt);
    }

	public static function getLabel(): string
	{
		return __('Simple');
	}
}
