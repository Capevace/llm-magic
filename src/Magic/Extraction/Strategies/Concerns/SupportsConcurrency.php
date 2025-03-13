<?php

namespace Mateffy\Magic\Extraction\Strategies\Concerns;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Mateffy\Magic\Extraction\Artifacts\Artifact;
use VXM\Async\AsyncFacade as Async;

trait SupportsConcurrency
{
	/**
	 * Process batches in parallel.
	 *
	 * @param Collection<Collection<Artifact>> $batches
	 * @param Closure(Collection<Artifact>): ?array $execute The function that does the LLM call.
	 * @param null|Closure(array): void $process The function to process each resulting data array.
	 */
	protected function runConcurrently(Collection $batches, Closure $execute, ?Closure $process = null): Collection
	{
		$concurrency = config('llm-magic.extraction.concurrency');

		$results = collect();

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

				$results->push($data);
			}

			return $results;
		}

		// If the concurrency is higher than 1, we can run the batches concurrently using the number of forks specified.

		// We again chunk the batches into groups of $concurrency.
		$concurrentSteps = $batches->chunk($concurrency);

		// Run the concurrent steps. We split the batches into groups of $concurrency and run them concurrently to not cause any memory issues.
		foreach ($concurrentSteps as $concurrentBatches) {
			foreach ($concurrentBatches as $batch) {
				Async::run(function () use ($batch, $execute) {
					$result = $execute($batch);

					return $result;
				});
			}

			$returnedResults = Async::wait();
			$returnedResults = json_decode(json_encode($returnedResults), associative: true, flags: JSON_THROW_ON_ERROR);

			foreach ($returnedResults as $result) {
				// Filter out invalid results.
				if ($result === null) {
					continue;
				}

				// Process the result.
				if ($process) {
					$process($result);
				}

				$results->push($result);
			}
		}

		return $results;
	}
}