<?php

namespace Mateffy\Magic\Artifacts\Content;

use Mateffy\Magic\LLM\Message\MultimodalMessage\Base64Image;

interface EmbedContent extends Content
{
    public function getPath(): string;
    public function getMimeType(): string;
    public function isAbsolutePath(): string;
}
