<?php

namespace Mateffy\Magic\Extraction;

interface Extractor
{
	public function process(): array;
}