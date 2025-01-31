<?php

namespace Mateffy\Magic\Artifacts\Content;

interface TextualContent extends Content
{
    public function text(): string;
}