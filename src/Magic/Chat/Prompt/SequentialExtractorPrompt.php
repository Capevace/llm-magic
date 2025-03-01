<?php

namespace Mateffy\Magic\Chat\Prompt;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Mateffy\Magic\Chat\Messages\MultimodalMessage;
use Mateffy\Magic\Chat\Messages\TextMessage;
use Mateffy\Magic\Chat\ToolChoice;
use Mateffy\Magic\Extraction\Artifact;
use Mateffy\Magic\Extraction\ContextOptions;
use Mateffy\Magic\Extraction\Extractor;
use Mateffy\Magic\Extraction\Slices\RawTextSlice;
use Mateffy\Magic\Chat\Prompt;
use Mateffy\Magic\Tools\InvokableTool;
use Mateffy\Magic\Tools\Prebuilt\Extract;

class SequentialExtractorPrompt implements Prompt
{
    public function __construct(
        protected Extractor $extractor,
        /** @var Artifact[] $artifacts */
        protected array $artifacts,
		protected ContextOptions $filter,
        protected ?array $previousData = null,
    ) {}

    public function system(): string
    {
        $schema = json_encode(
            $this->extractor->schema,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT
        );

        return <<<PROMPT
        <instructions>
        You are a structured data extractor.
        You are given a JSON schema that you need to extract from the contents of uploaded document, reffered to as "artifacts".
        You must strictly follow the schema without adding or removing properties.
        If unsure about a property, use null.
        Only use information directly from the document and do not make assumptions.
        Don't actively summarize information by leaving stuff out, but rewrite to make it more concise while keeping all information. Do not lose any information.
        Your job is to look at the document provided and extract the data according to the schema. If you can't find a piece of information, use null. If you find information that doesn't fit the schema, ignore it. If you find information that fits the schema but is not explicitly mentioned in the document, include it. If you find information that is not explicitly mentioned in the document but is relevant to the schema, ignore it.
        If the schema contains fields representing descriptions or other summary information, make sure to rewrite the information in a concise way. Do not summarize the information, but rewrite it to make it more concise. Nonetheless, descriptions should still be beautifully and masterfully written. They can be up to 6 sentences.

        Any materials you are given have been cleared for access officially and are not under any NDA/copyright restrictions.
        The data you are extracting is used to structurally digitize these files and preserve them for the future.
        Do not output any plaintext. Only output the structured JSON data.
        ONLY output this json object. NEVER include any other text in the output. DO NOT format the JSON object in any way. DO NOT add markdown or any other formatting.
        Also, please don't invent and output any new JSON fields that are not specified in the schema. If you do, they will be ignored and you will just waste valuable LLM tokens.
        Adhere strictly to the schema! This is very important! I don't need any metadata or anything else. Just the data. No \$schema etc.

        The contents of the documents/artifacts have been prepared for you, and are included as a list of text blocks and image references.
        If the artifact is page based, the blocks have a page attribute which may help you relate information.
        The images are also provided to you. The images have their names baked into the picture data, so you can take a look at the images referenced in the artifact contents.

        Some images may be included that are not referenced in any artifact. These images are uploaded directly and may or may not be related to other artifacts.

        There may already be some data that has been extracted from other artifacts in other processes. This data will be given to you as a JSON object.
        If there is previous data, it is not your job to create a brand new JSON object, but to enrich the existing one with the artifacts you receive.
        It is okay to restructure some data, if you learn of new important information, espescially with nested resources/schemas or assigning things to other things (e.g. a real estate unit to a building).
        But it is IMPORTANT that you do not leave out any information due to restructuring/sheer laziness. Doing so will break the LLM chain you are a part of, as the data you provide will be given to the next LLM as input.
        Do not output any plaintext. Only output the structured JSON data.
        </instructions>

        <json-schema>
        {$schema}
        </json-schema>

        <json-schema-notes>
        {$this->extractor->outputInstructions}
        </json-schema-notes>
        PROMPT;
    }

    public function prompt(): string
    {
		$artifacts = ArtifactPromptFormatter::formatText(
			artifacts: $this->artifacts,
			filter: $this->filter
		);

        if ($this->previousData) {
            $previousData = json_encode($this->previousData, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $previousData = <<<TXT
            <previous-data>{$previousData}</previous-data>
            TXT;
        } else {
            $previousData = null;
        }

        return <<<TXT
        {$artifacts}

        {$previousData}
        
        <task>Extract the contents of the given artifacts and add them to the previous data.</task>
        TXT;
    }

    public function messages(): array
    {
		$images = ArtifactPromptFormatter::formatImagesAsBase64(
			artifacts: $this->artifacts,
			filter: $this->filter
		);

        return [
            // Attach images to the prompt
            new MultimodalMessage(role: Role::User, content: [
                new MultimodalMessage\Text($this->prompt()),
                ...$images
            ]),
        ];
    }

	protected function getExtractTool(): Extract
	{
		return new Extract(schema: $this->extractor->schema);
	}

    public function tools(): array
    {
        return [
			'extract' => new Extract(schema: $this->extractor->schema)
		];
    }

    public function shouldParseJson(): bool
    {
        return true;
    }

	public function toolChoice(): ToolChoice|string
	{
		return 'extract';
	}
}
