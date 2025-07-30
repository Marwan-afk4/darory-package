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
            __DIR__ . '/../resources/views/elements' => resource_path('views/elements'),
            __DIR__ . '/../resources/views/layouts' => resource_path('views/layouts'),
            __DIR__ . '/../resources/views/livewire' => resource_path('views/livewire'),
            __DIR__ . '/../resources/views/auth' => resource_path('views/auth'),
            //livewire class to view
        ], 'darory-views');

        $this->publishes([
            __DIR__ . '/Components' => app_path('View/Components'),
        ], 'darory-components');

        $this->publishes([
            __DIR__ . '/../public/phoenix' => public_path('phoenix'),
        ], 'darory-public');

        $this->publishes([
            __DIR__ . '/../app/livewire' => app_path('livewire'),
        ], 'darory-livewire-classes');

        $this->publishes([
            __DIR__ . '/../app/Helper' => app_path('Helper'),
        ], 'darory-helper-api-response');

        $this->publishes([
            __DIR__ . '/../app/Resources' => app_path('Resources'),
        ], 'darory-user-resources');

        $this->publishes([
            __DIR__ . '/../app/Http/Requests' => app_path('Http/Requests'),
        ], 'darory-requests');
        
        $this->publishes([
            __DIR__ . '/../app/Http/Controllers' => app_path('Http/Controllers'),
        ], 'darory-controllers');

        //routes
        $this->publishes([
            __DIR__ . '/../routes/web.php' => base_path('routes/web.php'),
        ], 'darory-routes');

        //api
        $this->publishes([
            __DIR__ . '/../routes/api.php' => base_path('routes/api.php'),
        ], 'darory-api-routes');

        

        // $this->publishes([
        //     __DIR__.'/stubs' => base_path('stubs'),
        // ], 'darory-stubs');
    }

    public function register()
    {
        //
    }
}
