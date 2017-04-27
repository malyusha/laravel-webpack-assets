<?php

use Illuminate\Support\Arr;

if(!function_exists('webpack')) {
    function webpack($chunk = null, $secure = null) {
        $assets = app('webpack.assets');

        if($chunk === null) {
            return $assets;
        }

        return $assets->url($chunk, $secure);
    }
}

if(!function_exists('webpack_script')) {
    /**
     * @param $chunkName
     *
     * @param array $attributes
     * @param null $secure
     * @return null|string
     */
    function webpack_script($chunkName, array $attributes = [], $secure = null): string {
        return app('webpack.assets')->script($chunkName, $attributes, $secure);
    }
}

if(!function_exists('webpack_style')) {
    /**
     * @param $chunkName
     *
     * @param array $attributes
     * @param null $secure
     * @return null|string
     */
    function webpack_style($chunkName, array $attributes = [], $secure = null): string {
        return app('webpack.assets')->style($chunkName, $attributes, $secure);
    }
}