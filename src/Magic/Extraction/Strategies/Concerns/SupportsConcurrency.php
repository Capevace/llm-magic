<?php

namespace Mateffy\Magic\Extraction\Strategies\Concerns;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Concurrency;
use Mateffy\Magic\Extraction\Artifacts\Artifact;

trait SupportsConcurrency
{
	/**
	 * Process batches in parallel.
	 *
	 * @param Collection<Collection<Artifact>> $batches
	 * @param Closure(Collection<Artifact>): ?array $execute The function that does the LLM call.
	 * @param Closure(array): void $process The function to process each resulting data array.
	 */
	protected function runConcurrently(Collection $batches, Closure $execute, Closure $process): void
	{
		$concurrency = config('llm-magic.extraction.concurrency', 1);

		// If concurrency is 1 or less, consider it disabled.
		if ($concurrency <= 1) {
			// No concurrency.
			foreach ($batches as $batch) {
				// Generate a result.
				$data = $execute($batch);

				// Filter out invalid results.
				if ($data === null) {
					continue;
				}

				// Process the result.
				$process($data);
			}

			return;
		}

		// If the concurrency is higher than 1, we can run the batches concurrently using the number of forks specified.

		// We again chunk the batches into groups of $concurrency.
		$concurrentSteps = $batches->chunk($concurrency);

		foreach ($concurrentSteps as $concurrentBatches) {
			// Run the concurrent steps.
			$results = Concurrency::driver('fork')
				->run(
					$concurrentBatches
						// While these nested functions look a bit odd, they are necessary as
						// Concurrency::driver('fork')->run() expects a Closure that it can serialize
						->map(fn(Collection $artifacts) => fn() => $execute($artifacts))
						->all()
				);

			foreach ($results as $result) {
				// Filter out invalid results.
				if ($result === null) {
					continue;
				}

				// Process the result.
				$process($result);
			}
		}
	}
}