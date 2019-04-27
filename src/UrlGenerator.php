<?php

namespace Malyusha\WebpackAssets;

interface UrlGenerator
{
    /**
     * Returns url to given path.
     *
     * @param string $path
     *
     * @return string
     */
    public function url(string $path): string;

    /**
     * Returns file path with prefix for given path.
     *
     * @param string $path
     *
     * @return string
     */
    public function path(string $path): string;
}