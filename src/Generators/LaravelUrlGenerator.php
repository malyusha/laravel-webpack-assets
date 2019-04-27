<?php

namespace Malyusha\WebpackAssets\Generators;

class LaravelUrlGenerator implements \Malyusha\WebpackAssets\UrlGenerator
{
    /**
     * @var \Illuminate\Filesystem\FilesystemAdapter
     */
    protected $adapter;

    public function __construct(\Illuminate\Filesystem\FilesystemAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * Returns url to given path.
     *
     * @param string $path
     *
     * @return string
     */
    public function url(string $path): string
    {
        return $this->adapter->url($path);
    }

    /**
     * Returns file path with prefix for given path.
     *
     * @param string $path
     *
     * @return string
     */
    public function path(string $path): string
    {
        return $this->adapter->path($path);
    }
}