<?php

namespace Mateffy\Magic\Models;

use Illuminate\Support\Collection;
use Mateffy\Magic\Models\Options\ChatGptOptions;
use Mateffy\Magic\Models\Options\ElElEmOptions;
use Mateffy\Magic\Models\Options\Organization;
use Mateffy\Magic\Models\Providers\UsesOpenAiApi;

class OpenAI extends ElElEm
{
    use UsesOpenAiApi;

    public function __construct(
        string $model,
        ElElEmOptions $options = new ChatGptOptions,
    ) {
        parent::__construct(
            organization: new Organization(
                id: 'openai',
                name: 'OpenAI',
                website: 'https://openai.com',
                privacyUsedForModelTraining: true,
                privacyUsedForAbusePrevention: true,
            ),
            model: $model,
            options: $options,
        );
    }

    public static function models(?string $prefix = 'openai', ?string $prefixLabels = 'OpenAI'): Collection
    {
        return static::prefixModels([
            'o1-preview' => 'GPT-o1 Preview',
            'o1-mini' => 'GPT-o1 Mini',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'gpt-4o' => 'GPT-4o',
            'gpt-4o-mini' => 'GPT-4o Mini',
        ], $prefix, $prefixLabels);
    }

	public static function gpt_4o_mini(ElElEmOptions $options = new ChatGptOptions): OpenAI
	{
		return new OpenAI('gpt-4o-mini');
	}

	public static function gpt_4o(ElElEmOptions $options = new ChatGptOptions): OpenAI
	{
		return new OpenAI('gpt-4o');
	}

	public static function gpt_4_turbo(ElElEmOptions $options = new ChatGptOptions): OpenAI
	{
		return new OpenAI('gpt-4-turbo');
	}

	public static function o1_mini(ElElEmOptions $options = new ChatGptOptions): OpenAI
	{
		return new OpenAI('o1-mini');
	}

	public static function o1_preview(ElElEmOptions $options = new ChatGptOptions): OpenAI
	{
		return new OpenAI('o1-preview');
	}
}
