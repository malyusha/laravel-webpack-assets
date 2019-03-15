<?php


namespace Malyusha\WebpackAssets;

use Blade;
use Illuminate\Support\ServiceProvider;
use Malyusha\WebpackAssets\Exceptions\AssetException;

class WebpackAssetsServiceProvider extends ServiceProvider
{
    /**
     * Publish the plugin configuration.
     */
    public function boot()
    {
        $this->publishes([__DIR__ . '/../config/assets.php' => config_path('assets.php')]);

        $this->app->alias('webpack.assets', Facade::class);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('webpack.assets', function () {
            $config = $this->app['config']['assets'];
            $seetings = array_merge($config, [
                'file' => public_path(config('assets.file')),
            ]);
            return new Asset($config, $this->app, $this->app['url']);
        });

        $this->mergeConfigFrom(__DIR__ . '/../config/assets.php', config_path('assets.php'));
    }

    public function provides()
    {
        return ['webpack.assets'];
    }
}