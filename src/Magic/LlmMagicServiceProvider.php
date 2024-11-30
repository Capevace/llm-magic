<?php

namespace Mateffy\Magic;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LlmMagicServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->setBasePath(__DIR__ . '/..')
            ->name('llm-magic')
            ->hasConfigFile();
    }
}