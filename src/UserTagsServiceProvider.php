<?php

namespace MujahidAbbas\LaravelUserTags;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class UserTagsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('user-tags')
            ->hasConfigFile()
            ->hasMigrations([
                'create_user_tags_table',
                'create_user_taggables_table',
            ]);
    }
}
