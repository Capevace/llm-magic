<?php

namespace Mateffy\Magic\Artifacts;

use App\Models\ExtractionBucket;
use App\Models\File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * @method static mixed dispatch(ExtractionBucket $bucket, File $file)
 */
class GenerateArtifactJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected ExtractionBucket $bucket, protected File $file) {}

    /**
     * @throws ArtifactGenerationFailed
     */
    public function handle(): void
    {
        $this->file->artifact_status = ArtifactGenerationStatus::InProgress;
        $this->file->save();

        try {
            $artifact = FileArtifact::from(path: $this->file->getOriginalPath(), disk: $this->file->getOriginalDisk());

            // Call get contents to ensure the artifact is looked through and cached
            $artifact->refreshContents();

            $this->file->artifact_status = ArtifactGenerationStatus::Complete;
            $this->file->save();
        } catch (ArtifactGenerationFailed $e) {
            $this->file->artifact_status = ArtifactGenerationStatus::Failed;
            $this->file->save();

            throw $e;
        } catch (\Exception $e) {
            $this->file->artifact_status = ArtifactGenerationStatus::Failed;
            $this->file->save();

            throw new ArtifactGenerationFailed($e->getMessage(), previous: $e);
        }
    }
}
