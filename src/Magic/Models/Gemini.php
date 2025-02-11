<?php

namespace Mateffy\Magic\Models;

use Illuminate\Support\Collection;
use Mateffy\Magic\Chat\MessageCollection;
use Mateffy\Magic\Chat\Prompt;
use Mateffy\Magic\Models\Options\ChatGptOptions;
use Mateffy\Magic\Models\Options\ElElEmOptions;
use Mateffy\Magic\Models\Options\Organization;
use Mateffy\Magic\Models\Providers\UsesOpenAiApi;

class Gemini extends ElElEm
{
	public const GEMINI_1_5_FLASH = 'gemini-1.5-flash';
	public const GEMINI_2_0_FLASH = 'gemini-2.0-flash';
	public const GEMINI_2_0_FLASH_LITE = 'gemini-2.0-flash-lite-preview-02-05';

    use UsesOpenAiApi;

    public function __construct(
        string $model,
        ElElEmOptions $options = new ChatGptOptions,
    ) {
        parent::__construct(
            organization: new Organization(
                id: 'google',
                name: 'Google',
                website: 'https://google.com',
                privacyUsedForModelTraining: true,
                privacyUsedForAbusePrevention: true,
            ),
            model: $model,
            options: $options,
        );
    }

	public static function models(?string $prefix = 'openai', ?string $prefixLabels = 'OpenAI API'): Collection
    {
        return static::prefixModels([
            static::GEMINI_1_5_FLASH => 'Gemini 1.5 Flash',
			static::GEMINI_2_0_FLASH => 'Gemini 2.0 Flash',
			static::GEMINI_2_0_FLASH_LITE => 'Gemini 2.0 Flash Lite',
        ], $prefix, $prefixLabels);
    }

    protected function getOpenAiApiKey(): string
    {
        return config('llm-magic.apis.gemini.token');
    }

    protected function getOpenAiOrganization(): ?string
    {
        return null;
    }

    protected function getOpenAiBaseUri(): ?string
    {
        return 'generativelanguage.googleapis.com/v1beta/openai';
    }

    public function send(Prompt $prompt): MessageCollection
    {
        // TODO: Implement send() method.
    }

    public static function flash(ElElEmOptions $options = new ChatGptOptions): static
    {
        return static::flash_2($options);
    }

	public static function flash_2(ElElEmOptions $options = new ChatGptOptions): static
	{
		return new static(
			model: self::GEMINI_2_0_FLASH,
			options: $options,
		);
	}

	public static function flash_2_lite(ElElEmOptions $options = new ChatGptOptions): static
	{
		return new static(
			model: self::GEMINI_2_0_FLASH_LITE,
			options: $options,
		);
	}

	public static function flash_1_5(ElElEmOptions $options = new ChatGptOptions): static
	{
		return new static(
			model: self::GEMINI_1_5_FLASH,
			options: $options,
		);
	}
}
