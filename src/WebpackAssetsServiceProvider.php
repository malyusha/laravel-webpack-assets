<?php

namespace Malyusha\WebpackAssets;

use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Malyusha\WebpackAssets\Generators\LaravelUrlGenerator;

class WebpackAssetsServiceProvider extends ServiceProvider
{
    /**
     * Publish the plugin configuration.
     */
    public function boot()
    {
        $this->publishes([__DIR__.'/../config/assets_manifest.php' => $this->app->configPath('assets_manifest.php')], 'config');

        $this->app->singleton('webpack.assets', function () {
            $config = (array) $this->app->get('config')->get('assets_manifest');
            /**@var $filesystemFactory \Illuminate\Contracts\Filesystem\Factory */
            $filesystemFactory = $this->app->make(\Illuminate\Contracts\Filesystem\Factory::class);
            // Get the adaptor
            /**@var \Illuminate\Filesystem\FilesystemAdapter $adapter */
            $adapter = $filesystemFactory->disk(Arr::pull($config, 'disk'));
            $generator = new LaravelUrlGenerator($adapter);

            return new Asset($config, $generator, $adapter);
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/assets_manifest.php', 'assets_manifest');
    }

    public function provides()
    {
        return ['webpack.assets'];
    }
}