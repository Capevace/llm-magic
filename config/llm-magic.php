<?php

$builtin_default = 'openai/gpt-4o-mini';
$builtin_cheap = 'openai/gpt-4o-mini';
$builtin_default_embeddings = 'openai/' . \Mateffy\Magic\Embeddings\OpenAIEmbeddings::TEXT_EMBEDDING_3_SMALL;

return [
	'llm' => [
        'default' => env('LLM_MAGIC_MODEL', $builtin_default),
    ],
    'models' => [
        'default' => env('LLM_MAGIC_MODEL', $builtin_default),
		'cheap' => env('LLM_MAGIC_CHEAP_MODEL', $builtin_cheap),
		'extraction' => env('LLM_MAGIC_EXTRACTION_MODEL', null),
		'chat' => env('LLM_MAGIC_CHAT_MODEL', null),
		'embeddings' => env('LLM_MAGIC_EMBEDDINGS_MODEL', $builtin_default_embeddings),
    ],
	'extraction' => [
		'concurrency' => intval(env('LLM_MAGIC_EXTRACTION_CONCURRENCY', 3)),
	],
    'artifacts' => [
        'base' => env('LLM_MAGIC_ARTIFACTS_BASE', storage_path('app/magic-artifacts')),
        'disk' => env('LLM_MAGIC_ARTIFACTS_DISK', 'artifacts'),
        'prefix' => env('LLM_MAGIC_ARTIFACTS_PREFIX', ''),

		// This is the default max token count for an LLM model if it doesn't implement HasMaximumTokenCount.
		// This is used in the ArtifactSplitter to determine the maximum token count for each split when using .
		'default_max_tokens' => env('LLM_MAGIC_DEFAULT_MAX_TOKENS', 10000),
    ],
    'python' => [
        'cwd' => env('LLM_MAGIC_PYTHON_CWD', realpath(__DIR__ . '/../python')),
        'uv' => [
            'path' => env('LLM_MAGIC_PYTHON_UV_PATH', '/usr/bin/env uv'),
        ],
    ],
    'apis' => [
        'anthropic' => [
            'token' => env('ANTHROPIC_API_KEY'),
        ],
        'openai' => [
            'token' => env('OPENAI_API_KEY'),
            'organization_id' => env('OPENAI_ORGANIZATION_ID'),
        ],
        'groq' => [
            'token' => env('GROQ_API_KEY'),
        ],
        'openrouter' => [
            'token' => env('OPENROUTER_API_KEY'),
        ],
		'mistral' => [
			'token' => env('MISTRAL_API_KEY'),
		],
        'togetherai' => [
            'token' => env('TOGETHERAI_API_KEY'),
        ],
		'gemini' => [
			'token' => env('GEMINI_API_KEY'),
		],
		'deepseek' => [
			'token' => env('DEEPSEEK_API_KEY'),
		]
    ],
];
