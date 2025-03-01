<?php

namespace Mateffy\Magic\Support;

use Illuminate\Support\Facades\Log;
use Mateffy\Magic\Exceptions\PythonScriptException;

class PythonRunner
{
	public function __construct(
		protected string $script,
		protected string $args,
	)
	{
	}

	public function execute(): mixed
	{
		$pythonDir = config('llm-magic.python.cwd');

        $uvPath = config('llm-magic.python.uv.path');

        $currentDir = getcwd();

		try {
            // Change working directory to the artifact directory
            chdir($pythonDir);

			$command = "{$uvPath} run --isolated {$this->script} {$this->args}";
            $output = shell_exec($command);

			Log::debug("PythonRunner: $output");

            $json = json_decode($output, true);

            if (isset($json['error'])) {
                throw new PythonScriptException($json['error']);
            }

            return $json;
        } finally {
            chdir($currentDir);
        }
	}
}