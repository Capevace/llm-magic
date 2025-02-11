<?php

use Mateffy\Magic;

dataset('models', [
	fn () => Magic\Models\Gemini::flash_2_lite(),
	fn () => Magic\Models\OpenAI::gpt_4o(),
	fn () => Magic\Models\Anthropic::haiku(),
]);

it('system prompt is included in request', function (Magic\Models\LLM $model) {
    $text = Magic::chat()
		->model($model)
		->system('You are a codeword reminder. It is your job to remind the user of the codeword when asked. The codeword is "fish". Only respond with the codeword when asked.')
		->messages([
			Magic\Chat\Messages\TextMessage::user('What is the codeword?')
		])
//		->onMessage(fn ($message) => dump($message->content ?? $message->partial ?? ''))
		->stream()
		->lastText();

	expect($text)->toContain('fish');
})->with('models');

it('can generate a short story', function (Magic\Models\LLM $model) {
	// Show in progress text by resetting to beginning of line before outputting
	$output = function ($text) {
		echo "\r$text";
	};

	$story = Magic::chat()
		->model($model)
		->system('Generate a short story about a dragon and a knight.')
		->messages([
			Magic\Chat\Messages\TextMessage::user('Once upon a time, in a land far away, there was a dragon and a knight.')
		])
		->stream()
		->lastText();

	expect($story)->toContain('dragon');
	expect($story)->toContain('knight');
})->with('models');

it('can call a basic tool', function (Magic\Models\LLM $model) {
	$calledAdd = 0;
	$calledSubtract = 0;

	$data = Magic::chat()
		->model($model)
		->system('You are a calculator. Please ALWAYS use the calculation tool to calculate the result of any math problem. DO NOT think before calculating, just call functions immediately. Always submit the final answer using the "submitAnswer" tool.')
		->messages([
			Magic\Chat\Messages\TextMessage::user('Please calculate (5 + 3) - 2')
		])
		->tools([
			'add' => function (int $a, int $b) use (&$calledAdd) {
				$calledAdd += 1;

				return $a + $b;
			},
			'subtract' => function (int $a, int $b) use (&$calledSubtract) {
				$calledSubtract += 1;

				return Magic::end(['answer' => $a - $b]);
			},
			'submitAnswer' => function (int $answer) {
				return Magic::end(['answer' => $answer]);
			}
		])
		->toolChoice()
		->stream();

	expect($calledAdd)->toBe(1);
	expect($calledSubtract)->toBe(1);
	expect($data->lastData()['answer'])->toBe(6);
})->with('models');