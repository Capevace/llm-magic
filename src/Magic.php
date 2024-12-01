<?php

namespace Mateffy;

use Closure;
use Illuminate\Support\Collection;
use Mateffy\Magic\Builder\ChatPreconfiguredModelBuilder;
use Mateffy\Magic\Builder\EmbeddingsBuilder;
use Mateffy\Magic\Builder\ExtractionLLMBuilder;
use Mateffy\Magic\Embeddings\OpenAIEmbeddingModel;
use Mateffy\Magic\Functions\Exceptions\ToolCallException;
use Mateffy\Magic\Functions\MagicReturnFunction;
use Mateffy\Magic\LLM\Exceptions\UnableToActAsFunction;
use Mateffy\Magic\LLM\Exceptions\UnknownInferenceException;
use Mateffy\Magic\LLM\LLM;
use Mateffy\Magic\LLM\Message\TextMessage;
use Mateffy\Magic\LLM\Models\Claude3Family;
use Mateffy\Magic\LLM\Models\Gpt4Family;
use Mateffy\Magic\LLM\Models\OpenRouter;
use Mateffy\Magic\LLM\Models\TogetherAI;
use Mateffy\Magic\Loop\EndConversation;
use Mateffy\Magic\Memory\MagicMemory;
use ReflectionException;
use Throwable;

class Magic
{
    public static function extract(): ExtractionLLMBuilder
    {
        return new ExtractionLLMBuilder;
    }

    public static function chat(): ChatPreconfiguredModelBuilder
    {
        return new ChatPreconfiguredModelBuilder;
    }

    public static function ask(string $prompt, array $schema = ['type' => 'string'], string|LLM|null $model = null, bool $sentence = true): mixed
    {
        if ($schema['type'] === 'string') {
            $sentenceNotice = $sentence
                ? 'You are expected to respond with a single sentence, straight to the point. Not just a single word, but a full sentence. For example, reply "The capital of France is Paris" instead of just "Paris".'
                : 'Try to respond with just the answer, no sentence around it. For example, reply "Paris" instead of "The capital of France is Paris"';
        } else {
            $sentenceNotice = 'concise and to the point';
        }


        $response = Magic::chat()
            ->model($model ?? self::getDefaultModel())
            ->system(<<<PROMPT
            <instructions>
            You are an expert question answerer. 
            You are concise and try to keep it short, but you fill it with high quality information in response to the question. 
            
            <character>
            You don't need to do all the chatbot assistant messaging (saying sorry, giving alternatives, etc.). 
            If you can't answer a question, just state so concisely, and do not give other information or ask a question that wasn't asked. 
            Avoid using multiple lines of text unless necessary.
            </character> 
            
            <output-format>
            By default your response will just be a simple string value message, 
            but the schema can be changed by the user and may be structured data. 
            In that case return the data in the 'returnValue' field.
            </output-format>
            
            <response-length>
            {$sentenceNotice}
            </response-length>
            </instructions>
            PROMPT)
            ->messages([
                TextMessage::user($prompt)
            ])
            ->tools([
                'extract' => new Magic\Functions\Extract([
                    'type' => 'object',
                    'properties' => [
                        'returnValue' => $schema
                    ]
                ])
            ])
            ->toolChoice('extract')
            ->stream();

        return $response->firstFunctionOutput()?->output['returnValue'] ?? null;
    }

    public static function end(mixed $output): EndConversation
    {
        return new EndConversation($output);
    }

    /**
     * @param string $description
     * @param 'string'|'number'|'boolean'|'array'|'object' $type
     * @param array|null $schema
     * @param LLM|string|null $llm
     * @return mixed
     * @throws UnableToActAsFunction
     * @throws UnknownInferenceException
     * @throws ReflectionException
     */
    public static function function(string $description, string $type, ?array $schema = null, LLM|string|null $llm = null): mixed
    {
        $messages = Magic::chat()
            ->model($llm ?? self::getDefaultModel())
            ->system(<<<PROMPT
            You are a function that returns a value of type {$type}.

            For example, you may be asked to return the year of release of a song as a number.
            You can return this data by calling the `returnValue` function.

            If the task you are given is not possible for you to do or answer, you can call the `fail` function with an error message.
            PROMPT)
            ->tools([
                'returnValue' => new MagicReturnFunction($type, $schema),
                'fail' => fn (string $error) => Magic::end($error),
            ])
            ->messages([
                TextMessage::user($description)
            ])
            ->stream();

        $output = $messages
            ->firstFunctionOutput();

        if ($output->call->name === 'returnValue') {
            return $output->output;
        } elseif ($output->call->name === 'fail') {
            throw new UnableToActAsFunction($output->output);
        } else {
            throw new UnknownInferenceException('LLM did not return a valid response: '.json_encode($output));
        }
    }

    public static function memory(): MagicMemory
    {
        return new MagicMemory;
    }

    public static function getDefaultModel(): LLM
    {
        return Claude3Family::haiku();
    }

    public static function embeddings(Closure|string|null $input = null, ?OpenAIEmbeddingModel $model = null): EmbeddingsBuilder
    {
        $builder = new EmbeddingsBuilder;

        if ($input) {
            $builder->input($input);
        }

        if ($model) {
            $builder->model($model);
        }

        return $builder;
    }

    public static function error(string $message, ?string $code = null, ?Throwable $previous = null): ToolCallException
    {
        return new ToolCallException($message, $code, $previous);
    }

    public static function models(): Collection
    {
        return collect([
            ...Claude3Family::models(),
            ...Gpt4Family::models(),
            ...OpenRouter::models(),
            ...TogetherAI::models(),
        ])
            ->sortBy(fn ($name, $key) => $key);
    }
}
