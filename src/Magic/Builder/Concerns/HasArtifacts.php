<?php

namespace Mateffy\Magic\Builder\Concerns;

use Mateffy\Magic\Extraction\Artifacts\Artifact;
use Mateffy\Magic\Extraction\Artifacts\DiskArtifact;

trait HasArtifacts
{
    /** @var Artifact[] */
    protected array $artifacts = [];

    public function file(string $path, ?string $disk = null, bool $replace = false): static
    {
        if ($replace) {
            $this->artifacts = [];
        }

        $this->artifacts[] = DiskArtifact::from(path: $path, disk: $disk);

        return $this;
    }

    /**
     * @param  string[]  $paths
     */
    public function files(array $paths, bool $replace = false): static
    {
        if ($replace) {
            $this->artifacts = [];
        }

        foreach ($paths as $path) {
            $this->file($path);
        }

        return $this;
    }

    public function artifact(Artifact $artifact, bool $replace = false): static
    {
        if ($replace) {
            $this->artifacts = [];
        }

        $this->artifacts[] = $artifact;

        return $this;
    }

    /**
     * @param  Artifact[]  $artifacts
     */
    public function artifacts(array $artifacts, bool $replace = false): static
    {
        if ($replace) {
            $this->artifacts = [];
        }

        foreach ($artifacts as $artifact) {
            $this->artifact($artifact);
        }

        return $this;
    }

    public function getArtifacts(): array
    {
        return $this->artifacts;
    }
}
