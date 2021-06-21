<?php

namespace Distilleries\Contentful;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Package Laravel specific internal name.
     *
     * @var string
     */
    protected $package = 'contentful';

    /**
     * {@inheritdoc}
     */
    public function provides(): array
    {
        return [
            'command.contentful.model',
            'command.contentful.migration',
            'command.contentful.sync',
            'command.contentful.sync-data',
            'command.contentful.sync-flatten',
            'command.contentful.sync-locales',
            'command.contentful.import-clean',
            'command.contentful.import-publish',
            'contentful.rich-text.parser'
        ];
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../../config/config.php' => base_path('config/' . $this->package . '.php'),
        ], 'config');

        $this->registerMigrations();
    }

    protected function registerMigrations()
    {
        if (ContentfulUtilities::$runsMigrations && $this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations/');
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/config.php', $this->package);

        $this->app->bind(Api\DeliveryApi::class, Api\Delivery\Cached::class);
        $this->app->bind(Api\ManagementApi::class, Api\Management\Api::class);
        $this->app->bind(Api\SyncApi::class, Api\Sync\Api::class);
        $this->app->bind(Api\UploadApi::class, Api\Upload\Api::class);

        $this->registerContentfulRelated();

        if ($this->app->runningInConsole()) {
            $this->registerCommands();
        }
    }

    /**
     * Register Artisan commands.
     *
     * @return void
     */
    private function registerCommands()
    {
        $this->app->singleton('command.contentful.model', function () {
            return new Commands\Generators\Models(app(Api\ManagementApi::class));
        });
        $this->app->singleton('command.contentful.migration', function () {
            return new Commands\Generators\Migrations(app(Api\ManagementApi::class));
        });
        $this->app->singleton('command.contentful.sync', function () {
            return new Commands\Sync\Sync;
        });
        $this->app->singleton('command.contentful.sync-switch', function () {
            return new Commands\Sync\SyncSwitch;
        });
        $this->app->singleton('command.contentful.sync-data', function () {
            return new Commands\Sync\SyncData(app(Api\SyncApi::class));
        });
        $this->app->singleton('command.contentful.sync-flatten', function () {
            return new Commands\Sync\SyncFlatten;
        });
        $this->app->singleton('command.contentful.sync-locales', function () {
            return new Commands\Sync\SyncLocales(app(Api\ManagementApi::class));
        });
        $this->app->singleton('command.contentful.import-clean', function () {
            return new Commands\Import\ImportClean(app(Api\ManagementApi::class));
        });
        $this->app->singleton('command.contentful.import-publish', function () {
            return new Commands\Import\ImportPublish(app(Api\ManagementApi::class));
        });

        $this->commands('command.contentful.model');
        $this->commands('command.contentful.migration');
        $this->commands('command.contentful.sync');
        $this->commands('command.contentful.sync-switch');
        $this->commands('command.contentful.sync-data');
        $this->commands('command.contentful.sync-flatten');
        $this->commands('command.contentful.sync-locales');
        $this->commands('command.contentful.import-clean');
        $this->commands('command.contentful.import-publish');
    }

    /**
     * Bind utilities in IoC.
     *
     * @return void
     */
    private function registerContentfulRelated()
    {
        $this->app->singleton('contentful.rich-text.parser', function () {
            $spaceId = config('contentful.space_id');
            $environment = config('contentful.environment');

            $client = new \Contentful\Delivery\Client(config('contentful.tokens.delivery.live'), $spaceId, $environment);
            $linkResolver = new \Contentful\Delivery\LinkResolver(
                $client,
                new \Contentful\Delivery\ResourcePool\Extended(
                    $client,
                    new \Cache\Adapter\Void\VoidCachePool
                )
            );

            return new \Contentful\RichText\Parser($linkResolver);
        });
    }
}
