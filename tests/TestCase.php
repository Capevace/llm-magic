<?php

namespace Mateffy\Magic\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Mateffy\Magic\LlmMagicServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'VendorName\\Skeleton\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LlmMagicServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
		// Resolve correct path __DIR__.'/../.env'; wont work
		$path = realpath('../llm-magic');

		if (file_exists($path)) {
			$dotenv = \Dotenv\Dotenv::createImmutable($path);
			$dotenv->load();

        }

		$app->loadEnvironmentFrom($path);

//		ANTHROPIC_API_KEY=
//		HUGGINGFACE_TOKEN=
//		OPENAI_API_KEY=
//		OPENAI_ORGANIZATION=
//		GEMINI_API_KEY=
//		OPENROUTER_API_KEY=
//		TOGETHERAI_API_KEY=
//		GROQ_API_KEY=
//		MAPBOX_ACCESS_TOKEN=

		$app['config']->set('llm-magic.apis.gemini.token', env('GEMINI_API_KEY'));
		$app['config']->set('llm-magic.apis.openai.token', env('OPENAI_API_KEY'));
		$app['config']->set('llm-magic.apis.openai.organization', env('OPENAI_ORGANIZATION'));
		$app['config']->set('llm-magic.apis.huggingface.token', env('HUGGINGFACE_TOKEN'));
		$app['config']->set('llm-magic.apis.anthropic.token', env('ANTHROPIC_API_KEY'));
		$app['config']->set('llm-magic.apis.openrouter.token', env('OPENROUTER_API_KEY'));
		$app['config']->set('llm-magic.apis.togetherai.token', env('TOGETHERAI_API_KEY'));
		$app['config']->set('llm-magic.apis.groq.token', env('GROQ_API_KEY'));
		$app['config']->set('llm-magic.apis.deepseek.token', env('DEEPSEEK_API_KEY'));
        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}
