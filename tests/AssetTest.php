<?php

use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use Malyusha\WebpackAssets\Asset;

class AssetTest extends TestCase
{
    protected $file;

    /**
     * @var \Malyusha\WebpackAssets\Asset
     */
    protected $asset;

    public function setUp()
    {
        parent::setUp();

        $app = new Container();
        Container::setInstance($app);

        $asset = Mockery::mock(\Illuminate\Contracts\Routing\UrlGenerator::class);
        $asset->shouldReceive('asset')->andReturn('http://site.com');

        $this->file = __DIR__ . '/fixtures/assets.json';
        $this->asset = new Asset($this->file, $asset);
    }

    /**
     * @covers Asset::assets()
     */
    public function test_it_reads_json_with_assets()
    {
        $content = json_decode(file_get_contents($this->file), true);

        $this->assertSame($content, $this->asset->assets());
    }

    /**
     * @covers Asset::searchExtension()
     */
    public function test_it_automatically_searches_needed_extension()
    {
        $file = 'background.jpg';
        $this->assertEquals($this->asset->path($file), 'assets/images/background.jpg');
    }
}