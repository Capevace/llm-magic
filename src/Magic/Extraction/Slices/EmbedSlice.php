<?php

namespace Mateffy\Magic\Extraction\Slices;

interface EmbedSlice extends Slice
{
    public function getPath(): string;
    public function getMimeType(): string;
    public function isAbsolutePath(): string;
}
