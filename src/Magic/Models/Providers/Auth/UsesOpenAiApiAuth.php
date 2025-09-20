<?php

namespace Mateffy\Magic\Models\Providers\Auth;

use Mateffy\Magic\Support\ApiTokens\TokenResolver;
use OpenAI;
use OpenAI\Client;

trait UsesOpenAiApiAuth
{
	protected function getOpenAiApiKey(): string
    {
		return app(TokenResolver::class)->resolve('openai');
    }

    protected function getOpenAiOrganization(): ?string
    {
		return app(TokenResolver::class)->resolve('openai', 'organization_id');
    }

    protected function getOpenAiBaseUri(): ?string
    {
		try {
			return app(TokenResolver::class)->resolve('openai', 'base_uri') ?? 'api.openai.com/v1';
		} catch (\Exception $e) {
			return 'api.openai.com/v1';
		}
    }

	protected function createClient(): Client
	{
		return OpenAI::factory()
			->withApiKey($this->getOpenAiApiKey())
			->withOrganization($this->getOpenAiOrganization())
			->withBaseUri($this->getOpenAiBaseUri())
            ->withHttpClient(
                new \GuzzleHttp\Client([
                    'timeout' => 1200,
                ])
            )
			->make();
	}
}
