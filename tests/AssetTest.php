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

    /**
     * @var \Mockery\MockInterface
     */
    protected $urlMock;

    public function setUp()
    {
        parent::setUp();

        $app = new Container();
        Container::setInstance($app);

        $this->urlMock = Mockery::mock(\Illuminate\Contracts\Routing\UrlGenerator::class);
        $this->urlMock->shouldReceive('asset')->andReturn('http://site.com');

        $this->file = __DIR__ . '/fixtures/assets.json';
        $this->asset = new Asset($this->file, $this->urlMock);
    }

    /**
     * @covers Asset::assets()
     */
    public function test_it_reads_json_with_assets()
    {
        $content = $this->getJson();

        $this->assertSame($content, $this->asset->assets());
    }

    /**
     * @covers Asset::searchExtension()
     */
    public function test_it_returns_correct_file_relative_path()
    {
        $file = 'main.js';
        $this->assertEquals($this->asset->path($file), 'assets/main.js');
    }

    protected function getJson()
    {
        return json_decode(file_get_contents($this->file), true);
    }
}