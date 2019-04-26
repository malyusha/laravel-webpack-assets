<?php

namespace Malyusha\WebpackAssets;

use Blade;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Malyusha\WebpackAssets\PathGenerators\LaravelPathGenerator;

class WebpackAssetsServiceProvider extends ServiceProvider
{
    /**
     * Publish the plugin configuration.
     */
    public function boot()
    {
        $this->publishes([__DIR__.'/../config/assets.php' => $this->app->configPath('assets.php')]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(PathGenerator::class, function () {
            return new LaravelPathGenerator($this->app->get(FilesystemAdapter::class));
        });

        $this->app->singleton('webpack.assets', function () {
            $config = $this->app['config']->get('assets');
            $pathGenerator = $this->app->get(PathGenerator::class);
            $filesystem = $this->app->make(Factory::class)->disk($config['disk']);

            return new Asset(Arr::pull($config, 'disk'), $pathGenerator, $filesystem);
        });

        $this->mergeConfigFrom(__DIR__.'/../config/assets.php', $this->app->configPath('assets.php'));
    }

    public function provides()
    {
        return ['webpack.assets'];
    }
}