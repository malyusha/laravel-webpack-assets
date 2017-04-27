<?php


namespace Malyusha\WebpackAssets;


class Facade extends \Illuminate\Support\Facades\Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'webpack.assets';
    }
}