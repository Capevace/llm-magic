<?php

use Mateffy\Magic;
use Mateffy\Magic\Extraction\Artifacts\VirtualArtifact;
use Mateffy\Magic\Extraction\Slices\RawTextSlice;

it('system prompt is included in request', function () {
    $data = Magic::extract()
		->model(Magic\Models\Gemini::flash_2_lite())
		->system('What is the password?')
		->schema([
			'type' => 'object',
			'properties' => [
				'password' => [
					'type' => 'string',
				],
			],
		])
		->strategy('simple')
		->artifacts([
			new VirtualArtifact(
				new Magic\Extraction\Artifacts\ArtifactMetadata(
					type: Magic\Extraction\Artifacts\ArtifactType::Text,
					name: 'password.txt',
					mimetype: 'text/plain',
					extension: 'txt',
				),
				rawContents: 'the password is elephant',
				contents: [new RawTextSlice(text: 'the password is elephant')],
				text: 'the password is elephant',
			)
		])
		->stream();

	expect($data)->toHaveKey('password');
	expect($data['password'])->toBe('elephant');
});
