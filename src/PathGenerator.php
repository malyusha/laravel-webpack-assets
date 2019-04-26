<?php

namespace Malyusha\WebpackAssets;

interface PathGenerator
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
     * Returns absolute path to given path.
     *
     * @param string $path
     *
     * @return string
     */
    public function path(string $path): string;
}