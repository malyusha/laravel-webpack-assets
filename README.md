<p align="center">
    <img src="https://travis-ci.org/malyusha/laravel-webpack-assets.svg?branch=master">
</p>

# Laravel Webpack Assets
Package that allows you to include assets from json file, generated by 
[Webpack Manifest Plugin](https://github.com/danethurber/webpack-manifest-plugin)

## Installation

Require the latest version of package using [Composer](https://getcomposer.org/) 

`$ composer require malyusha/laravel-webpack-assets`

If you are using version of laravel < 5.5, you need to add service provider into your `config/app.php`
file in `providers` section:
* `\Malyusha\WebpackAssets\WebpackAssetsServiceProvider::class`

You can add the WebpackAssets facade in `facades` section:
* `'WebpackAssets' => \Malyusha\WebpackAssets\Facade::class`


## Configuration

To change package configuration you need to publish configuration file:

`$ php artisan vendor:publish --tag=config`

This will publish `assets_manifest.php` file inside your `config` directory.
Configuration file has a few options:

* `file` - path to manifest.json file, relative to `disk` path. See `disk` option;
* `fail_on_load` - whether to fail on load assets file. If true - exception will be thrown;
* `disk` - where your `manifest.json` and all assets file are located. See laravel `config/filesystems.php -> disks`.

## Usage

Package provides helper functions to include script and style HTML elements inside blade templates:

* `webpack_script($script)` - will generate `<script src="path_to_script_from_manifest_file"></script>`;
* `webpack_style($script`- will do the same as `webpack_script` but for style;
* `webpack($chunkName = null)` - will return instance of `Asset` class if no arguments provided, otherwise returns asset url with host.

## Examples

Let's imagine, that you have generated `manifest.json` file with such content:
```json
{
  "app.js": "/assets/1b53147322421b069bf1.js",
  "auth.background.png": "/assets/e60cc0de08eee2256222218425eaa943.png",
  "auth.login.css": "/assets/css/20a7e7e51e1f142b2b1f.css",
  "auth.login.js": "/assets/20a7e7e51e1f142b2b1f.js",
  "auth.password.forgot.css": "/assets/css/280c079c4407fbd0fdca.css",
  "auth.password.forgot.js": "/assets/280c079c4407fbd0fdca.js"
}
```

### Retrieving paths

```php
$webpackAssets = webpack();
// Full urls with hostname of filesystem. E.g. if disk driver is set to `public`, and `url` option
// is env('APP_URL').'/storage'
echo $webpackAssets->url('app.js'); // Output - http://host.dev/storage/assets/1b53147322421b069bf1.js
echo $webpackAssets->url('app.css'); // Output - http://host.dev/storage/assets/css/20a7e7e51e1f142b2b1f.css

// Absolute path for given filesystem. E.g. if disk driver is set to `public` and `root` parameter set to
// storage_path('app/public')
echo $webpackAssets->path('app.js'); // Output - /{project_dir}/storage/app/public/assets/1b53147322421b069bf1.js

// Relative paths, as they're presented inside json file
echo $webpackAssets->chunkPath('app.js'); // Output - /assets/1b53147322421b069bf1.js

```

### Using in blade templates

Whenever you want to output your asset simply write:

```blade
{!! webpack_script('app.js') !!}

// or

{!! webpack()->image('background.png', 'Background') !!} 
// Output: <img alt="Background" src="http://host.dev/assets/e60cc0de08eee2256222218425eaa943.png">
```

### Raw file contents

When you need to add inline file content, such as css or js wrapped with `style` or `script` tags
you can now use helper functions for that: `webpack_raw_style` and `webpack_raw_script`, or if
in object - `webpack()->rawStyle()` and `webpack()->rawScript()`.

```blade
{!! webpack_raw_style('css/main.css') !!}

{{-- Or --}}

{!! webpack()->rawStyle('css/main.css') !!}

{{--Output--}}
<style>...content of main.css file...</style>
```

