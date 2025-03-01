<?php

namespace Mateffy\Magic\Extraction;

use Illuminate\Support\Collection;
use Mateffy\Magic\Extraction\Slices\Slice;

class ArtifactSplitter
{
	public function __construct(
		protected Artifact $original,
		/** @var Collection<Slice> $contents */
		protected Collection $contents,
		protected int $maxTokens,
	)
	{
	}

	/**
	 * @param Collection<Slice>|Slice[] $contents
	 * @return array{0: Collection<Collection<Artifact>>, 1: int}
	 */
	public static function split(Artifact $artifact, array|Collection $contents, int $maxTokens): array
	{
		// We use Laravel Dependency Injection to create a new instance of ArtifactSplitter.
		// This way, library users can override the default implementation of ArtifactSplitter with their own.
		$splitter = app(ArtifactSplitter::class, [
			'original' => $artifact,
			'contents' => Collection::wrap($contents),
			'maxTokens' => $maxTokens,
		]);

		return $splitter->splitByTokens();
	}

	/**
	 * Splits the document. Adds data until either the character limit or embed limit is reached, then starts a new split.
	 *
	 * @return array{0: Collection<Collection<Artifact>>, 1: int}
	 */
	public function splitByTokens(): array
	{
		$artifacts = collect();
        $contents = collect();

        $currentTokens = 0;
        $totalTokens = 0;

        foreach ($this->contents as $content) {
			$tokens = $content->getTokens();

            $currentTokens += $tokens;
			$totalTokens += $tokens;

			$contents[] = $content;

            if ($currentTokens > $this->maxTokens) {
                $artifacts[] = new SplitArtifact(original: $this->original, contents: $contents, tokens: $currentTokens);
                $contents = collect();

                $currentTokens = 0;
            }
        }

        if (! empty($contents)) {
            $artifacts[] = new SplitArtifact(original: $this->original, contents: $contents, tokens: $currentTokens);
        }

        return [$artifacts, $totalTokens];
	}
}