<?php

namespace Mateffy\Magic\Media;

interface Image
{
	public function getMime(): string;
	public function getContents(): mixed;
	public function getStream(): mixed;
}