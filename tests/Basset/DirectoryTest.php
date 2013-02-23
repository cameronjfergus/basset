<?php

use Mockery as m;
use JasonLewis\Basset\FilterFactory;

class DirectoryTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testRequireDirectory()
    {
        $files = $this->getFilesMock();
        $filterFactory = $this->getFilterFactoryMock();

        $assetFactory = m::mock('JasonLewis\Basset\AssetFactory[getAbsolutePath]', array($files, $filterFactory, 'path/to/public', 'testing'));
        $assetFactory->shouldReceive('getAbsolutePath')->once()->with('baz/foo.css')->andReturn('baz/foo.css');
        $assetFactory->shouldReceive('getAbsolutePath')->once()->with('baz/bar.css')->andReturn('baz/bar.css');

        $assets = array(
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo')
        );
        $assets[0]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[1]->shouldReceive('isFile')->once()->andReturn(false);
        $assets[2]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[0]->shouldReceive('getPathname')->once()->andReturn('baz/foo.css');
        $assets[2]->shouldReceive('getPathname')->once()->andReturn('baz/bar.css');

        $directory = m::mock('JasonLewis\Basset\Directory[iterateDirectory]', array($files, $assetFactory, $filterFactory, 'baz'));
        $directory->shouldReceive('iterateDirectory')->once()->with('baz')->andReturn($assets);
        $directory->requireDirectory();

        $assets = $directory->getAssets();

        $this->assertEquals('baz/foo.css', $assets[0]->getAbsolutePath());
        $this->assertEquals('baz/bar.css', $assets[1]->getAbsolutePath());
    }


    public function testRecursivelyRequireDirectory()
    {
        $files = $this->getFilesMock();
        $filterFactory = $this->getFilterFactoryMock();

        $assetFactory = m::mock('JasonLewis\Basset\AssetFactory[getAbsolutePath]', array($files, $filterFactory, 'path/to/public', 'testing'));
        $assetFactory->shouldReceive('getAbsolutePath')->once()->with('baz/foo.css')->andReturn('baz/foo.css');
        $assetFactory->shouldReceive('getAbsolutePath')->once()->with('baz/qux/bar.css')->andReturn('baz/qux/bar.css');

        $assets = array(
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo')
        );
        $assets[0]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[1]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[0]->shouldReceive('getPathname')->once()->andReturn('baz/foo.css');
        $assets[1]->shouldReceive('getPathname')->once()->andReturn('baz/qux/bar.css');

        $directory = m::mock('JasonLewis\Basset\Directory[recursivelyIterateDirectory]', array($files, $assetFactory, $filterFactory, 'baz'));
        $directory->shouldReceive('recursivelyIterateDirectory')->once()->with('baz')->andReturn($assets);
        $directory->requireTree();

        $assets = $directory->getAssets();

        $this->assertEquals('baz/foo.css', $assets[0]->getAbsolutePath());
        $this->assertEquals('baz/qux/bar.css', $assets[1]->getAbsolutePath());
    }


    public function testExcludeAndRequireSpecificFilesInDirectory()
    {
        $files = $this->getFilesMock();
        $filterFactory = $this->getFilterFactoryMock();

        $assetFactory = m::mock('JasonLewis\Basset\AssetFactory[getAbsolutePath]', array($files, $filterFactory, 'path/to/public', 'testing'));
        $assetFactory->shouldReceive('getAbsolutePath')->once()->with('baz/foo.css')->andReturn('baz/foo.css');
        $assetFactory->shouldReceive('getAbsolutePath')->once()->with('baz/qux/bar.css')->andReturn('baz/qux/bar.css');

        $assets = array(
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo')
        );
        $assets[0]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[1]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[0]->shouldReceive('getPathname')->once()->andReturn('baz/foo.css');
        $assets[1]->shouldReceive('getPathname')->once()->andReturn('baz/qux/bar.css');

        $directory = m::mock('JasonLewis\Basset\Directory[recursivelyIterateDirectory]', array($files, $assetFactory, $filterFactory, 'baz'));
        $directory->shouldReceive('recursivelyIterateDirectory')->once()->with('baz')->andReturn($assets);
        $directory->requireTree()->only('baz/foo.css');

        $assets = $directory->getAssets();

        $this->assertEquals('baz/foo.css', $assets[0]->getAbsolutePath());

        $directory->except('baz/foo.css');

        $this->assertEmpty($directory->getAssets());
    }


    public function testFiltersAreAppliedToEntireDirectory()
    {
        $files = $this->getFilesMock();
        $config = $this->getConfigMock();
        $config->shouldReceive('has')->once()->with('basset::filters.FooFilter')->andReturn(false);

        $filterFactory = new FilterFactory($config);

        $assetFactory = m::mock('JasonLewis\Basset\AssetFactory[getAbsolutePath]', array($files, $filterFactory, 'path/to/public', 'testing'));
        $assetFactory->shouldReceive('getAbsolutePath')->once()->with('baz/foo.css')->andReturn('baz/foo.css');
        $assetFactory->shouldReceive('getAbsolutePath')->once()->with('baz/bar.css')->andReturn('baz/bar.css');
        $assets = array(
            m::mock('SplFileInfo'),
            m::mock('SplFileInfo')
        );
        $assets[0]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[1]->shouldReceive('isFile')->once()->andReturn(true);
        $assets[0]->shouldReceive('getPathname')->once()->andReturn('baz/foo.css');
        $assets[1]->shouldReceive('getPathname')->once()->andReturn('baz/bar.css');

        $directory = m::mock('JasonLewis\Basset\Directory[iterateDirectory]', array($files, $assetFactory, $filterFactory, 'baz'));
        $directory->shouldReceive('iterateDirectory')->once()->with('baz')->andReturn($assets);
        $directory->requireDirectory()->apply('FooFilter');

        $directory->processFilters();

        $assets = $directory->getAssets();

        $filters = $assets[0]->getFilters();
        $this->assertInstanceOf('JasonLewis\Basset\Filter', $filters['FooFilter']);

        $filters = $assets[1]->getFilters();
        $this->assertInstanceOf('JasonLewis\Basset\Filter', $filters['FooFilter']);
    }


    protected function getFilesMock()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }


    protected function getConfigMock()
    {
        return m::mock('Illuminate\Config\Repository');
    }


    protected function getAssetFactoryMock()
    {
        return m::mock('JasonLewis\Basset\AssetFactory');
    }


    protected function getFilterFactoryMock()
    {
        return m::mock('JasonLewis\Basset\FilterFactory');
    }


}