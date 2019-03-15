<?php

namespace Malyusha\WebpackAssets;

use Illuminate\Support\Arr;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Foundation\Application;
use Malyusha\WebpackAssets\Exceptions\AssetException;

class Asset
{
    protected $assets = [];

    /**
     * Array of cached files content retrieved via "content" method.
     *
     * @var array
     */
    protected static $cachedFileContents = [];

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    public function __construct(array $config, Application $application, UrlGenerator $url)
    {
        $this->checkConfiguration($config);

        $this->url = $url;
        $this->file = $config['file'];
        $this->app = $application;

        if (! file_exists($this->file)) {
            try {
                throw new AssetException("File {$this->file} does not exist.");
            } catch (AssetException $exception) {
                if ((bool) $config['fail_on_load']) {
                    // If configuration tells us to fail on load, we'll throw exception forward
                    throw $exception;
                }
            }
        } else {
            $this->assets = json_decode(file_get_contents($this->file), true) ?: [];
        }
    }

    /**
     * Returns all loaded assets from file.
     *
     * @return array
     */
    public function assets(): array
    {
        return $this->assets;
    }

    /**
     * Checks configuration array for validity.
     *
     * @param array $config
     *
     * @throws Exceptions\InvalidConfigurationException
     */
    private function checkConfiguration(array $config)
    {
        foreach ($required = ['fail_on_load', 'file'] as $item) {
            if (! array_key_exists($item, $config)) {
                throw new \Malyusha\WebpackAssets\Exceptions\InvalidConfigurationException($required);
            }
        }
    }

    /**
     * Generates style link for chunk.
     *
     * @param $chunkName
     * @param array $attributes
     * @param null $secure
     *
     * @return string
     */
    public function style($chunkName, array $attributes = [], $secure = null): string
    {
        $defaults = ['media' => 'all', 'type' => 'text/css', 'rel' => 'stylesheet'];

        $attributes = $attributes + $defaults;

        $attributes['href'] = $url = $this->url($chunkName, $secure);

        return $url ? $this->toHtmlString('<link'.$this->attributes($attributes).'>'.PHP_EOL) : '';
    }

    /**
     * Generates inline styles with content of given chunk.
     *
     * @param $chunkName
     * @param array $attributes
     *
     * @return string
     */
    public function rawStyle($chunkName, array $attributes = []): string
    {
        $content = $this->content($chunkName);

        return $content ? '<style'.$this->attributes($attributes).'>'.$content.'</style>' : '';
    }

    /**
     * Generates inline script with content of given chunk.
     *
     * @param $chunkName
     * @param array $attributes
     *
     * @return string
     */
    public function rawScript($chunkName, array $attributes = []): string
    {
        $content = $this->content($chunkName);

        return $content ? '<script type="text/javascript"'.$this->attributes($attributes).'>'.$content.'</script>' : '';
    }

    /**
     * Generates script attribute for chunk.
     *
     * @param $chunkName
     * @param array $attributes
     * @param null $secure
     *
     * @return string
     */
    public function script($chunkName, array $attributes = [], $secure = null): string
    {
        $attributes['src'] = $url = $this->url($chunkName, $secure);

        return $url ? $this->toHtmlString('<script'.$this->attributes($attributes).'></script>'.PHP_EOL) : '';
    }

    /**
     * Generates HTML image element for chunk.
     *
     * @param $chunkName
     * @param null $alt
     * @param array $attributes
     * @param null $secure
     *
     * @return string
     */
    public function image($chunkName, $alt = null, array $attributes = [], $secure = null): string
    {
        $defaults = ['alt' => $alt];

        $attributes += $defaults;

        $attributes['src'] = $url = $this->url($chunkName, $secure);

        return $url ? $this->toHtmlString('<img'.$this->attributes($attributes).'>') : '';
    }

    /**
     * Returns full url for chunk.
     *
     * @param $chunkName
     * @param null $secure
     *
     * @return string
     */
    public function url($chunkName, $secure = null): string
    {
        $path = $this->path($chunkName);

        return $path ? $this->url->asset($this->path($chunkName), $secure) : '';
    }

    /**
     * Retrieves chunk from assets array.
     *
     * @param string $chunkName Name of chunk
     * @param bool $absolute Returns absolute path from server to public directory if true.
     *
     * @return string
     */
    public function path($chunkName, $absolute = false): string
    {
        $path = Arr::get($this->assets, $chunkName, '');
        $relativePath = str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, 'public'.DIRECTORY_SEPARATOR.$path);

        return $absolute ? $this->app->basePath($relativePath) : $path;
    }

    /**
     * Returns content of chunk.
     *
     * @param $chunk
     *
     * @return string
     */
    public function content($chunk): string
    {
        $path = $this->path($chunk, true);

        if (array_key_exists($path, static::$cachedFileContents)) {
            return static::$cachedFileContents[$path];
        }

        return (string) (static::$cachedFileContents[$path] = file_get_contents($path));
    }

    /**
     * Build an HTML attribute string from an array.
     *
     * @param array $attributes
     *
     * @return string
     */
    public function attributes($attributes): string
    {
        $html = [];

        foreach ((array) $attributes as $key => $value) {
            $element = $this->attributeElement($key, $value);

            if ($element !== null) {
                $html[] = $element;
            }
        }

        return count($html) > 0 ? ' '.implode(' ', $html) : '';
    }

    /**
     * Convert all applicable characters to HTML entities.
     *
     * @param string $value
     *
     * @return string
     */
    public function escapeAll($value): string
    {
        return htmlentities($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Build a single attribute element.
     *
     * @param string $key
     * @param string $value
     *
     * @return string
     */
    protected function attributeElement($key, $value): string
    {
        // For numeric keys we will assume that the key and the value are the same
        // as this will convert HTML attributes such as "required" to a correct
        // form like required="required" instead of using incorrect numerics.
        if (is_numeric($key)) {
            $key = $value;
        }

        if ($value !== null) {
            return $key.'="'.$this->escapeAll($value).'"';
        }

        return '';
    }

    /**
     * Transform the string to an Html serializable object.
     *
     * @param $html
     *
     * @return \Illuminate\Support\HtmlString
     */
    public function toHtmlString($html): \Illuminate\Support\HtmlString
    {
        return new \Illuminate\Support\HtmlString($html);
    }
}