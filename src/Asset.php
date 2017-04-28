<?php


namespace Malyusha\WebpackAssets;


use Illuminate\Support\Arr;
use Illuminate\Contracts\Routing\UrlGenerator;
use Malyusha\WebpackAssets\Exceptions\AssetException;

class Asset
{
    protected $assets = [];

    /**
     * @var UrlGenerator
     */
    protected $url;

    public function __construct($file, UrlGenerator $url)
    {
        $this->url = $url;
        $this->file = $file;

        if(!file_exists($file)) {
            throw new AssetException("File {$file} does not exist.");
        }

        $this->assets = json_decode(file_get_contents($file), true) ?: [];
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
     * Generates style link for chunk.
     *
     * @param $chunkName
     * @param array $attributes
     * @param null $secure
     * @return string
     */
    public function style($chunkName, array $attributes = [], $secure = null): string
    {
        $defaults = ['media' => 'all', 'type' => 'text/css', 'rel' => 'stylesheet'];

        $attributes = $attributes + $defaults;

        $attributes['href'] = $url = $this->url($chunkName, $secure);

        return $url ? $this->toHtmlString('<link' . $this->attributes($attributes) . '>' . PHP_EOL) : '';
    }

    /**
     * Generates script attribute for chunk.
     *
     * @param $chunkName
     * @param array $attributes
     * @param null $secure
     * @return string
     */
    public function script($chunkName, array $attributes = [], $secure = null): string
    {
        $attributes['src'] = $url = $this->url($chunkName, $secure);

        return $url ? $this->toHtmlString('<script' . $this->attributes($attributes) . '></script>' . PHP_EOL) : '';
    }

    /**
     * Generates HTML image element for chunk.
     *
     * @param $chunkName
     * @param null $alt
     * @param array $attributes
     * @param null $secure
     * @return string
     */
    public function image($chunkName, $alt = null, array $attributes = [], $secure = null): string
    {
        $defaults = ['alt' => $alt];

        $attributes += $defaults;

        $attributes['src'] = $url = $this->url($chunkName, $secure);

        return $url ? $this->toHtmlString('<img' . $this->attributes($attributes) . '>') : '';
    }

    /**
     * Returns full url for chunk.
     *
     * @param $chunkName
     * @param null $secure
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
     * @param $chunkName
     * @return string
     */
    public function path($chunkName): string
    {
        return Arr::get($this->assets, $chunkName, '');
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

        return count($html) > 0 ? ' ' . implode(' ', $html) : '';
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
            return $key . '="' . $this->escapeAll($value) . '"';
        }

        return '';
    }

    /**
     * Transform the string to an Html serializable object.
     *
     * @param $html
     * @return \Illuminate\Support\HtmlString
     */
    public function toHtmlString($html): \Illuminate\Support\HtmlString
    {
        return new \Illuminate\Support\HtmlString($html);
    }
}