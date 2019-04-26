<?php

namespace Malyusha\WebpackAssets;

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
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function register()
    {
        $config = $this->app['config']->get('assets');
        /**@var \Illuminate\Contracts\Filesystem\Factory $factory*/
        $factory = $this->app->make(\Illuminate\Contracts\Filesystem\Factory::class);
        $filesystem = $factory->disk($config['disk']);

        $this->app->bind(PathGenerator::class, function () use ($filesystem) {
            return new LaravelPathGenerator($filesystem);
        });

        $this->app->singleton('webpack.assets', function () use ($config, $filesystem) {
            $config = $this->app['config']->get('assets');
            $pathGenerator = $this->app->get(PathGenerator::class);

            return new Asset(Arr::pull($config, 'disk'), $pathGenerator, $filesystem, $filesystem);
        });

        $this->mergeConfigFrom(__DIR__.'/../config/assets.php', $this->app->configPath('assets.php'));
    }

    public function provides()
    {
        return ['webpack.assets'];
    }
}