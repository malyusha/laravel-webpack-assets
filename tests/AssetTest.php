<?php

use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Malyusha\WebpackAssets\Asset;
use PHPUnit\Framework\TestCase;

class AssetTest extends TestCase
{
    protected $manifestFile;

    /**
     * @var \Mockery\MockInterface
     */
    protected $urlGeneratorMock;

    /**
     * @var \Mockery\MockInterface
     */
    protected $fileSystemMock;

    /**
     * @var Asset
     */
    protected $assetsInstance;

    public function setUp()
    {
        parent::setUp();

        $app = new Container();
        Container::setInstance($app);

        $this->manifestFile = __DIR__.'/testdata/assets.json';

        $this->urlGeneratorMock = Mockery::mock(\Malyusha\WebpackAssets\UrlGenerator::class);
        $this->fileSystemMock = Mockery::mock(\Illuminate\Contracts\Filesystem\Filesystem::class);

        $this->assetsInstance = $this->getBaseAssetInstance();
    }

    /**
     * @covers Asset::assets()
     */
    public function test_it_reads_json_with_assets()
    {
        $this->fileSystemMock->shouldReceive('get')->once()->with($this->manifestFile)->andReturnUsing(function () {
            return file_get_contents($this->manifestFile);
        });

        $content = $this->getJsonContent($this->manifestFile);

        $this->assertSame($content, $this->assetsInstance->assets());
    }

    /**
     * @covers \Malyusha\WebpackAssets\Asset::fresh()
     */
    public function test_it_fresh_json_with_assets()
    {
        $this->fileSystemMock->shouldReceive('get')->andReturnUsing(function () {
            return file_get_contents($this->manifestFile);
        });
        $this->assertNotEmpty($this->assetsInstance->assets(), 'Assets array should not be empty');
        $content = file_get_contents($this->manifestFile);
        file_put_contents($this->manifestFile, '{}');
        $this->assertEmpty($this->assetsInstance->fresh()->assets());
        file_put_contents($this->manifestFile, $content);
    }

    /**
     * @covers Asset::path()
     */
    public function test_it_returns_correct_path_for_chunk()
    {
        $this->fileSystemMock->shouldReceive('get')->andReturn(file_get_contents($this->manifestFile));
        $this->urlGeneratorMock->shouldReceive('path')
            ->with('assets/main.js')
            ->andReturn('/absolute/path/to/assets/main.js');
        $this->assertEquals('/absolute/path/to/assets/main.js', $this->assetsInstance->path('main.js'));
    }

    /**
     * @covers Asset::path()
     * @covers Asset::content()
     */
    public function test_it_returns_raw_file_content()
    {
        $this->fileSystemMock->shouldReceive('get')->once()->andReturn(file_get_contents($this->manifestFile));
        $this->fileSystemMock->shouldReceive('get')->once()->andReturn($this->getFileContent('css/main.css'));
        $this->urlGeneratorMock->shouldReceive('path')->andReturn('css/main.css');
        $this->assertEquals($this->getFileContent('css/main.css'), $this->assetsInstance->content('main.css'));
    }

    /**
     * @covers Asset::url()
     */
    public function test_it_returns_url()
    {
        $this->fileSystemMock->shouldReceive('get')->once()->andReturn(file_get_contents($this->manifestFile));

        $this->urlGeneratorMock->shouldReceive('url')
            ->with('assets/main.js')
            ->andReturn('http://localhost/assets/main.js');
        $this->assertEquals('http://localhost/assets/main.js', $this->assetsInstance->url('main.js'));
    }

    /**
     * @covers Asset::rawStyle()
     */
    public function test_it_returns_raw_style_node()
    {
        $this->fileSystemMock->shouldReceive('get')->once()->andReturn(file_get_contents($this->manifestFile));
        $this->fileSystemMock->shouldReceive('get')->once()->andReturn($this->getFileContent('css/main.css'));
        $this->urlGeneratorMock->shouldReceive('path')->once()->andReturn('css/main.css');

        $content = $this->getFileContent('css/main.css');

        $this->assertEquals("<style>{$content}</style>", $this->assetsInstance->rawStyle('main.css'));
    }

    /**
     * @covers Asset::rawScript()
     */
    public function test_it_returns_raw_script_node()
    {
        $content = $this->getFileContent('main.js');
        $this->fileSystemMock->shouldReceive('get')->once()->andReturn(file_get_contents($this->manifestFile));
        $this->fileSystemMock->shouldReceive('get')->once()->andReturn($content);
        $this->urlGeneratorMock->shouldReceive('path')->andReturn('path');

        $this->assertEquals("<script type=\"text/javascript\">{$content}</script>", $this->assetsInstance->rawScript('main.js'));
    }

    public function test_it_throws_exception_if_no_file_exist_and_configuration_tells_it_should()
    {
        $this->fileSystemMock->shouldReceive('get')->andThrow(FileNotFoundException::class);
        $this->expectException(\Malyusha\WebpackAssets\Exceptions\AssetException::class);
        $this->assetsInstance->assets();
    }

    public function test_it_does_not_throw_exception_if_no_file_exist_and_configuration_tells_it_should_not()
    {
        $instance = $this->getBaseAssetInstance(false);
        $this->fileSystemMock->shouldReceive('get')->andThrow(FileNotFoundException::class);

        $this->assertEquals([], $instance->assets());
    }

    /**
     * @param bool $failOnLoad
     *
     * @return \Malyusha\WebpackAssets\Asset
     */
    protected function getBaseAssetInstance($failOnLoad = true): Asset
    {
        return new Asset([
            'file'         => $this->manifestFile,
            'fail_on_load' => $failOnLoad,
        ], $this->urlGeneratorMock, $this->fileSystemMock);
    }

    protected function getFileContent(string $file)
    {
        return file_get_contents(__DIR__.'/testdata/public/assets/'.$file);
    }

    /**
     * @param string $file
     *
     * @return array
     */
    protected function getJsonContent(string $file): array
    {
        return json_decode(file_get_contents($file), true);
    }
}