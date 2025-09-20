<?php

namespace Mateffy\Magic\Models\Providers\Image;

use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\NoReturn;
use Mateffy\Magic\Exceptions\NoImageGenerated;
use Mateffy\Magic\Exceptions\UnsupportedFeatureError;
use Mateffy\Magic\Media\Image;
use Mateffy\Magic\Media\InMemoryImage;
use Mateffy\Magic\Models\Providers\Auth\UsesOpenAiApiAuth;
use Mateffy\Magic\Support\ApiTokens\TokenResolver;
use OpenAI\Responses\Images\CreateResponseData;

trait UsesOpenAiImageApi
{
	use UsesOpenAiApiAuth;

	protected function getOpenAiApiKey(): string
    {
		$resolver = Container::getInstance()->make(TokenResolver::class);

		try {
			return $resolver->resolve('openai-image');
		} catch (\Exception $e) {
			return $resolver->resolve('openai');
		}
	}

    protected function getOpenAiBaseUri(): ?string
    {
		$resolver = Container::getInstance()->make(TokenResolver::class);

		try {
			return $resolver->resolve('openai-image', 'base_uri');
		} catch (\Exception $e) {
			return $resolver->resolve('openai', 'base_uri');
		}
    }

	protected function getConfig(): array
	{
		return [
			'model' => $this->model,
			'background' => $this->background,
			'quality' => $this->quality,
			'moderation' => $this->moderation,
			'output_format' => $this->format,
			'output_compression' => $this->compression,
			'size' => $this->dimensions?->toString(),
		];
	}

	protected function getMime(): string
	{
		return match ($this->format) {
			'webp' => 'image/webp',
			'jpeg', 'jpg' => 'image/jpeg',
			default => 'image/png',
		};
	}

	/**
	 * @throws NoImageGenerated
	 * @throws UnsupportedFeatureError
	 */
	public function generate(string $prompt, ?Collection $images = null): Image
	{
		$client = $this->createClient();

		if ($images->count() > 1) {
			throw new UnsupportedFeatureError('OpenAI image generation only supports one input image at this time.');
		}

		if ($image = $images->first()) {
			/** @var Image $image */
			$stream = $image->getStream();

			$images = $client->images()->edit(array_filter([
				...$this->getConfig(),
				'prompt' => $prompt,
				'n' => 1,
				'image' => $stream
			]));
		} else {
			$images = $client->images()->create(array_filter([
				...$this->getConfig(),
				'prompt' => $prompt,
				'n' => 1,
			]));
		}



		if (count($images->data) === 0) {
			throw new NoImageGenerated;
		}

		$contents = base64_decode($images->data[0]->b64_json);
		$mime = $this->getMime();

		return new InMemoryImage(contents: $contents, mime: $mime);
	}

	/**
	 * @throws NoImageGenerated
	 */
	public function generateMany(int $count, string $prompt, ?Collection $images = null): Collection
	{
		$client = $this->createClient();

		$images = $client->images()->create(array_filter([
			...$this->getConfig(),
			'prompt' => $prompt,
			'n' => $count,
		]));

		if (count($images->data) === 0) {
			throw new NoImageGenerated;
		}

		return Collection::wrap($images->data)
			->map(fn (CreateResponseData $image) => new InMemoryImage(
				contents: base64_decode($image->b64_json),
				mime: $this->getMime(),
			));
	}
}
