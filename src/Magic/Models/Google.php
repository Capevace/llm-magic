<?php

namespace Mateffy\Magic\Models;

use Illuminate\Support\Collection;
use Mateffy\Magic\Chat\MessageCollection;
use Mateffy\Magic\Chat\Prompt;
use Mateffy\Magic\Models\Options\ChatGptOptions;
use Mateffy\Magic\Models\Options\ElElEmOptions;
use Mateffy\Magic\Models\Options\Organization;
use Mateffy\Magic\Models\Providers\LLM\UsesOpenAiApi;
use Mateffy\Magic\Support\ApiTokens\TokenResolver;

class Google extends ElElEm
{
	public const string GEMINI_1_5_FLASH = 'gemini-1.5-flash';
	public const string GEMINI_1_5_FLASH_8B = 'gemini-1.5-flash-8b';
	public const string GEMINI_1_5_PRO = 'gemini-1.5-pro';
	public const string GEMINI_2_0_FLASH = 'gemini-2.0-flash';
	public const string GEMINI_2_0_FLASH_LITE = 'gemini-2.0-flash-lite';

	// Experimental models
	public const string GEMINI_2_5_FLASH_PREVIEW = 'gemini-2.5-flash-preview-04-17';
	public const string GEMINI_2_5_PRO_PREVIEW = 'gemini-2.5-pro-preview-03-25';
	public const string GEMINI_2_5_PRO_EXPERIMENTAL = 'gemini-2.5-pro-exp-03-25';

	// Deprecated models

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

	public static function models(?string $prefix = 'google', ?string $prefixLabels = 'Google'): Collection
    {
        return static::prefixModels([
            static::GEMINI_1_5_FLASH => 'Gemini 1.5 Flash',
			static::GEMINI_2_0_FLASH => 'Gemini 2.0 Flash',
			static::GEMINI_2_0_FLASH_LITE => 'Gemini 2.0 Flash Lite',
			static::GEMINI_2_5_PRO_PREVIEW => 'Gemini 2.5 Pro Preview',
			static::GEMINI_2_5_PRO_EXPERIMENTAL => 'Gemini 2.5 Pro Experimental',
			static::GEMINI_2_5_FLASH_PREVIEW => 'Gemini 2.5 Flash Preview',
        ], $prefix, $prefixLabels);
    }

    protected function getOpenAiApiKey(): string
    {
        return app(TokenResolver::class)->resolve('google');
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

	public static function flash_2_5_flash_preview(ElElEmOptions $options = new ChatGptOptions): static
	{
		return new static(
			model: self::GEMINI_2_5_FLASH_PREVIEW,
			options: $options,
		);
	}

	public static function flash_2_5_flash(ElElEmOptions $options = new ChatGptOptions): static
	{
		return self::flash_2_5_flash_preview($options);
	}

	public function flash_2_5_pro_preview(ElElEmOptions $options = new ChatGptOptions): static
	{
		return new static(
			model: self::GEMINI_2_5_PRO_PREVIEW,
			options: $options,
		);
	}

	public function flash_2_5_pro_experimental(ElElEmOptions $options = new ChatGptOptions): static
	{
		return new static(
			model: self::GEMINI_2_5_PRO_EXPERIMENTAL,
			options: $options,
		);
	}

	public function flash_2_5_pro(ElElEmOptions $options = new ChatGptOptions): static
	{
		return self::flash_2_5_pro_experimental($options);
	}

	public function getModelCost(): ?ModelCost
	{
		return match ($this->model) {
			self::GEMINI_1_5_FLASH => ModelCost::withPricePerMillion(inputPricePerMillion: 0.15, outputPricePerMillion: 0.6),
			self::GEMINI_1_5_FLASH_8B => ModelCost::withPricePerMillion(inputPricePerMillion: 0.075, outputPricePerMillion: 0.3),
			self::GEMINI_1_5_PRO => ModelCost::withPricePerMillion(inputPricePerMillion: 2.5, outputPricePerMillion: 10),
			self::GEMINI_2_0_FLASH => ModelCost::withPricePerMillion(inputPricePerMillion: 0.1, outputPricePerMillion: 0.4),
			self::GEMINI_2_0_FLASH_LITE => ModelCost::withPricePerMillion(inputPricePerMillion: 0.075, outputPricePerMillion: 0.3),
			self::GEMINI_2_5_FLASH_PREVIEW => ModelCost::withPricePerMillion(inputPricePerMillion: 0.15, outputPricePerMillion: 0.6),
			self::GEMINI_2_5_PRO_PREVIEW => ModelCost::withPricePerMillion(inputPricePerMillion: 1.25, outputPricePerMillion: 5),
			self::GEMINI_2_5_PRO_EXPERIMENTAL => ModelCost::withPricePerMillion(inputPricePerMillion: 1.25, outputPricePerMillion: 5),
			default => null,
		};
	}
}
