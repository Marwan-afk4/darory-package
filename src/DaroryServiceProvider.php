<?php

namespace Darory\Tools;

use Darory\Tools\Commands\DaroryInstall;
use Darory\Tools\Commands\DaroryApiControllers;
use Darory\Tools\Commands\DaroryCleanTranslations;
use Darory\Tools\Commands\DaroryControllers;
use Darory\Tools\Commands\DaroryCrud;
use Darory\Tools\Commands\DaroryEnums;
use Darory\Tools\Commands\DaroryFlutterModel;
use Darory\Tools\Commands\DaroryMigrations;
use Darory\Tools\Commands\DaroryModels;
use Darory\Tools\Commands\DaroryRequests;
use Darory\Tools\Commands\DaroryResources;
use Darory\Tools\Commands\DaroryTranslations;
use Darory\Tools\Commands\DaroryViews;
use Illuminate\Support\ServiceProvider;

class DaroryServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                DaroryControllers::class,
                DaroryCrud::class,
                DaroryEnums::class,
                DaroryRequests::class,
                DaroryResources::class,
                DaroryViews::class,
                DaroryApiControllers::class,
                DaroryTranslations::class,
                DaroryCleanTranslations::class,
                DaroryModels::class,
                DaroryMigrations::class,
                DaroryFlutterModel::class,
                DaroryInstall::class,
            ]);
        }

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'darory');

        $this->publishes([
            __DIR__ . '/../resources/views/components' => resource_path('views/components'),
            __DIR__ . '/../resources/views/vendor/pagination' => resource_path('views/vendor/pagination'),
        ], 'darory-views');

        $this->publishes([
            __DIR__ . '/Components' => app_path('View/Components'),
        ], 'darory-components');

        $this->publishes([
            __DIR__ . '/../public/phoenix' => public_path('phoenix'),
        ], 'darory-public');


        // $this->publishes([
        //     __DIR__.'/stubs' => base_path('stubs'),
        // ], 'darory-stubs');
    }

    public function register()
    {
        //
    }
}
