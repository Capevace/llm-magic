<?php

namespace Mateffy\Magic\Builder\Concerns;

use Mateffy\Magic\Models\ElElEm;
use Mateffy\Magic\Models\LLM;

trait HasModel
{
    protected LLM $model;

    public function model(string|LLM $model): static
    {
        if ($model instanceof LLM) {
            $this->model = $model;
        } else {
			$preconfigured_models = config('llm-magic.models');

			if (array_key_exists($model, $preconfigured_models)) {
				$configured_model = $preconfigured_models[$model] ?? $preconfigured_models['default'];

				assert(!empty($configured_model), "No model configuration found for '$model' or 'default'");

				$this->model = ElElEm::fromString($configured_model);
			} else {
				$this->model = ElElEm::fromString($model);
			}
        }

        return $this;
    }

	public static function getDefaultModel(): LLM
	{
		$preconfigured_models = config('llm-magic.models.default');

		return ElElEm::fromString($preconfigured_models);
	}

	public function getModel(): LLM
	{
		if (!isset($this->model)) {
			$this->model = self::getDefaultModel();
		}

		return $this->model;
	}
}
