<?php

namespace Mateffy\Magic\Models\Image;

use Mateffy\Magic\Media\ImageDimensions;
use Mateffy\Magic\Models\ImageModel;
use Mateffy\Magic\Models\Providers\Image\UsesOpenAiImageApi;

class OpenAI implements ImageModel
{
	use UsesOpenAiImageApi;

	public const string MODEL_DALL_E_2 = 'openai/dall-e-2';
	public const string MODEL_DALL_E_3 = 'openai/dall-e-3';
	public const string MODEL_GPT_IMAGE_1 = 'openai/gpt-image-1';

	public function __construct(
		protected string $model = 'gpt-image-1',
		protected ?ImageDimensions $dimensions = null,
		protected ?string $format = null,
		protected ?string $background = null,
		protected ?string $quality = null,
		protected ?int $compression = null,
		protected ?string $moderation = null,
	) {
	}

	public static function models(): array
	{
		return [
			'openai/dall-e-2' => 'DALL-E 2',
			'openai/dall-e-3' => 'DALL-E 3',
			'openai/gpt-image-1' => 'GPT Image 1',
		];
	}
}