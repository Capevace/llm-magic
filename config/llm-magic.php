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
    'artifacts' => [
        'base' => env('LLM_MAGIC_ARTIFACTS_BASE', storage_path('app/magic-artifacts')),
        'disk' => env('LLM_MAGIC_ARTIFACTS_DISK', 'artifacts'),
        'prefix' => env('LLM_MAGIC_ARTIFACTS_PREFIX', ''),
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
            'url' => env('OPENROUTER_API_URL', 'https://openrouter.ai/api/v1'),
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

    'extractors' => [
        'crm' => [
            'title' => 'Kontakt/Firma importieren',
            'description' => 'Extrahiere Daten aus verschiedenen Dokumenten und importiere sie in dein CRM-System.',

            'files' => [
                'maxSize' => 50, // MB
                'maxNumberOfFiles' => null, // Unlimited
                'allowedTypes' => [
                    // Using MIME types
                    'application/pdf',
                    'image/jpeg',
                    'image/png',
                    'image/gif',

                    // Using presets
                    'documents',
                    'images',

                    // Using specific type/preset options
                    ['type' => 'application/pdf', 'extractImages' => false], // Don't extract images from PDFs
                    ['type' => 'images', 'maxSize' => 20], // Allow images up to 20 MB
                ],
            ],

            'llm' => [
                'model' => 'openai/gpt-4',
                'options' => [
                    'temperature' => 0.5,
                    'maxTokens' => 100,
                    'topP' => 0.9,
                ],
            ],

            'strategy' => 'simple', // "sequential", "parallel"

            'output' => [
                'format' => [ // JSON Schema
                    'contacts' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'string'],
                                'email' => ['type' => 'string', 'format' => 'email'],
                                'phone' => ['type' => 'string'],
                            ],
                            'required' => ['name'],
                        ],
                    ],
                    'companies' => [
                        'type' => 'array',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'string'],
                                'website' => ['type' => 'string', 'format' => 'uri'],
                                'phone' => ['type' => 'string', 'format' => 'phone'],
                            ],
                            'required' => ['name'],
                        ],
                    ],
                ],
            ],
        ],
    ],
];
