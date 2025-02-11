<?php

use Mateffy\Magic;
use Mateffy\Magic\Embeddings\EmbeddedData;
use Mateffy\Magic\Embeddings\OpenAIEmbeddings;

it('system prompt is included in request', function () {
    $embeddings = Magic::embeddings('World')
		->model(OpenAIEmbeddings::text_3_small())
		->get();

	expect($embeddings)->toBeInstanceOf(EmbeddedData::class);
	expect($embeddings->vectors)->toBeArray();
	expect($embeddings->vectors)->toHaveCount(1536);
});
