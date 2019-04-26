<?php

namespace Malyusha\WebpackAssets\PathGenerators;

class LaravelPathGenerator implements \Malyusha\WebpackAssets\PathGenerator
{
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
        return $this->adapter->path($path);
    }

    /**
     * Returns absolute path to given path.
     *
     * @param string $path
     *
     * @return string
     * @throws \RuntimeException
     */
    public function path(string $path): string
    {
        return $this->adapter->url($path);
    }
}