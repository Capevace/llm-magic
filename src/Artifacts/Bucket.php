<?php

namespace Mateffy\Magic\Artifacts;

class Bucket
{
    public function __construct(
        /** @var Artifact[] $artifacts */
        protected array $artifacts
    ) {}
}
