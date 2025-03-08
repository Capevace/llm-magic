<?php

namespace Mateffy\Magic\Extraction\Parsers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mateffy\Magic\Extraction\Artifacts\ArtifactType;
use Mateffy\Magic\Support\PythonRunner;

class PdfParser
{
    protected string $localPath;
    protected string $outputDir;

    public function __construct(
        protected string $path,
        protected ?string $disk = null,

		/**
		 * If the PDF we're parsing has been generated from a different type of artifact, we can pass this here.
		 * This is then put into the metadata.json artifact to allow to correctly re-generate the artifact.
		 */
		protected ArtifactType $originalType = ArtifactType::Pdf,
		protected string $mime = 'application/pdf',
		protected ?string $originalFilename = null,
    )
    {
        $this->outputDir = sys_get_temp_dir() . '/' . Str::random(40);
        $this->localPath = sys_get_temp_dir() . '/' . Str::random(40) . '.pdf';
    }

    protected function ensureFileDownloaded(): void
    {
        if (File::exists($this->localPath)) {
            return;
        } elseif ($this->disk) {
            $disk = Storage::disk($this->disk);
            $stream = $disk->readStream($this->path);

            File::put($this->localPath, $stream);
        } else {
            File::copy($this->path, $this->localPath);
        }
    }

    public function parse(string $id): string
    {
        $this->ensureFileDownloaded();

        File::ensureDirectoryExists($this->outputDir);

		$safeFile = escapeshellarg($this->localPath);
		$id = escapeshellarg($id);
		$artifact_type = escapeshellarg($this->originalType->value);
		$mime = escapeshellarg($this->mime);
		$filename = escapeshellarg($this->originalFilename);

		$runner = new PythonRunner(
			script: 'prepare-pdf.py',
			args: "{$this->outputDir} {$safeFile} {$id} {$artifact_type} {$mime} {$filename} -- --json",
		);

		$runner->execute();

		return $this->outputDir;
    }

    public function moveToStorage(string $disk, string $path): string
    {
        // Move files from the local artifact directory to the storage disk, which might be s3
        $storage = Storage::disk($disk);

        $walk = function (string $dir, string $storagePath) use (&$walk, $storage) {
            $files = File::files($dir);

            $storage->makeDirectory($storagePath);

            foreach ($files as $file) {
                $storage->putFileAs($storagePath, $file, basename($file));
            }

            $dirs = File::directories($dir);

            foreach ($dirs as $subdir) {
                $walk($subdir, "{$storagePath}/" . basename($subdir));
            }
        };

        $walk($this->outputDir, $path);

        $this->cleanup();

        return $path;
    }

    public function moveToPath(string $path): string
    {
        File::ensureDirectoryExists($path);

        // Move files from the local artifact directory to the given path
        $directories = File::directories($this->outputDir);

        foreach ($directories as $dir) {
            File::moveDirectory($dir, "{$path}/" . basename($dir));
        }

        $files = File::files($this->outputDir);

        foreach ($files as $file) {
            File::move($file, "{$path}/" . basename($file));
        }

        $this->cleanup();

        return $path;
    }

    public function cleanup(): void
    {
        File::deleteDirectory($this->outputDir);
        File::delete($this->localPath);
    }
}
