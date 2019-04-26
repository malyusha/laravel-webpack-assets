<?php

namespace Malyusha\WebpackAssets;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Malyusha\WebpackAssets\Exceptions\AssetException;
use Malyusha\WebpackAssets\Exceptions\InvalidConfigurationException;

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
     * @var \Malyusha\WebpackAssets\PathGenerator
     */
    protected $pathGenerator;

    /**
     * @var \Illuminate\Contracts\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * Manifest json file.
     *
     * @var string
     */
    protected $manifestFile;

    /**
     * Whether or not to fail with exception when trying to get non-existing file from JSON manifest.
     *
     * @var bool
     */
    protected $failOnLoad;

    /**
     * Asset constructor.
     *
     * @param array $config
     * @param \Malyusha\WebpackAssets\PathGenerator $pathGenerator
     * @param \Illuminate\Contracts\Filesystem\Filesystem $filesystem
     *
     * @throws \Malyusha\WebpackAssets\Exceptions\InvalidConfigurationException
     */
    public function __construct(array $config, PathGenerator $pathGenerator, Filesystem $filesystem)
    {
        $this->setupFromConfig($config);

        $this->pathGenerator = $pathGenerator;
        $this->filesystem = $filesystem;
    }

    /**
     * Returns all loaded assets from file.
     *
     * @return array
     * @throws \Malyusha\WebpackAssets\Exceptions\AssetException
     */
    public function assets(): array
    {
        if ($this->assets === null) {
            $this->fresh();
        }

        return $this->assets;
    }

    /**
     * Refreshes array of assets with new values from manifest.
     *
     * @return self
     *
     * @throws \Malyusha\WebpackAssets\Exceptions\AssetException
     */
    public function fresh(): self
    {
        $this->assets = $this->retrieveAssets();

        return $this;
    }

    /**
     * Retrieves assets from configuration file.
     *
     * @return array
     * @throws \Malyusha\WebpackAssets\Exceptions\AssetException
     */
    protected function retrieveAssets(): array
    {
        $contents = '{}';

        try {
            $contents = $this->filesystem->get($this->manifestFile);
        } catch (\Illuminate\Contracts\Filesystem\FileNotFoundException $exception) {
            if ($this->failOnLoad) {
                throw new AssetException("File {$this->manifestFile} does not exist.");
            }
        }

        return json_decode($contents, true) ?: [];
    }

    /**
     * Checks configuration array for validity.
     *
     * @param array $config
     *
     * @throws Exceptions\InvalidConfigurationException
     */
    private function setupFromConfig(array $config)
    {
        foreach ($required = ['fail_on_load', 'file'] as $item) {
            if (! array_key_exists($item, $config)) {
                throw new InvalidConfigurationException($required);
            }
        }

        $this->manifestFile = $config['file'];
        $this->failOnLoad = (bool) $config['fail_on_load'];
    }

    /**
     * Generates style link for chunk.
     *
     * @param $chunkName
     * @param array $attributes
     *
     * @return string
     */
    public function style($chunkName, array $attributes = []): string
    {
        $defaults = ['media' => 'all', 'type' => 'text/css', 'rel' => 'stylesheet'];

        $attributes = array_merge($defaults, $attributes);

        $attributes['href'] = $url = $this->url($chunkName);

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
     *
     * @return string
     */
    public function script($chunkName, array $attributes = []): string
    {
        $attributes['src'] = $url = $this->url($chunkName);

        return $url ? $this->toHtmlString('<script'.$this->attributes($attributes).'></script>'.PHP_EOL) : '';
    }

    /**
     * Generates HTML image element for chunk.
     *
     * @param $chunkName
     * @param null $alt
     * @param array $attributes
     *
     * @return string
     * @throws \Malyusha\WebpackAssets\Exceptions\AssetException
     */
    public function image($chunkName, $alt = null, array $attributes = []): string
    {
        $defaults = ['alt' => $alt];

        $attributes = array_merge($defaults, $attributes);

        $attributes['src'] = $url = $this->url($chunkName);

        return $url ? $this->toHtmlString('<img'.$this->attributes($attributes).'>') : '';
    }

    /**
     * Returns full url for chunk.
     *
     * @param $chunkName
     *
     * @return string
     * @throws \Malyusha\WebpackAssets\Exceptions\AssetException
     */
    public function url($chunkName): string
    {
        $path = $this->path($chunkName);

        return $path ? $this->pathGenerator->url($path) : '';
    }

    /**
     * Retrieves chunk from assets array.
     *
     * @param string $chunkName Name of chunk
     * @param bool $absolute Returns absolute path from server directory if true.
     *
     * @return string
     * @throws \Malyusha\WebpackAssets\Exceptions\AssetException
     */
    public function path($chunkName, $absolute = false): string
    {
        $path = Arr::get($this->assets(), $chunkName, '');

        if($path === '') {
            return '';
        }

        return $absolute ? $this->pathGenerator->path($path) : $path;
    }

    /**
     * Returns content of chunk.
     *
     * @param $chunk
     *
     * @return string
     * @throws \Malyusha\WebpackAssets\Exceptions\AssetException
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