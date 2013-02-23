<?php

use Mockery as m;

class CollectionTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testGetCollectionName()
    {
        $collection = $this->getStandardCollectionInstance();
        $this->assertEquals('foo', $collection->getName());
    }


    public function testAddBasicAsset()
    {
        $files = $this->getFiles();
        $files->shouldReceive('exists')->twice()->with('path/to/bar.css')->andReturn(true);
        $config = $this->getConfig();
        $config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
        $manager = new JasonLewis\Basset\AssetManager($files, 'path/to', 'local');
        $collection = new JasonLewis\Basset\Collection($files, $config, $manager, 'foo');
        $collection->add('bar.css');
        $assets = $collection->getAssets();
        $this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[0]);
    }


    public function testAddAliasedAsset()
    {
        $files = $this->getFiles();
        $files->shouldReceive('exists')->twice()->with('path/to/bar.css')->andReturn(true);
        $config = $this->getConfig();
        $config->shouldReceive('has')->once()->with('basset::assets.foo')->andReturn(true);
        $config->shouldReceive('get')->once()->with('basset::assets.foo')->andReturn('bar.css');
        $manager = new JasonLewis\Basset\AssetManager($files, 'path/to', 'local');
        $collection = new JasonLewis\Basset\Collection($files, $config, $manager, 'foo');
        $collection->add('foo');
        $assets = $collection->getAssets();
        $this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[0]);
    }


    public function testAddRemoteAssets()
    {
        $files = $this->getFiles();
        $config = $this->getConfig();
        $config->shouldReceive('has')->once()->with('basset::assets.http://foo.com/bar.css')->andReturn(false);
        $manager = new JasonLewis\Basset\AssetManager($files, 'path/to', 'local');
        $collection = new JasonLewis\Basset\Collection($files, $config, $manager, 'foo');
        $collection->add('http://foo.com/bar.css');
        $assets = $collection->getAssets();
        $this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[0]);
        $this->assertTrue($assets[0]->isRemote());
    }


    public function testAddAssetsFromDefinedDirectory()
    {
        $files = $this->getFiles();
        $files->shouldReceive('exists')->once()->with('path/to/bar.css')->andReturn(false);
        $files->shouldReceive('exists')->once()->with('path/to/nested/bar.css')->andReturn(true);
        $config = $this->getConfig();
        $config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
        $config->shouldReceive('get')->once()->with('basset::directories')->andReturn(array('nested' => 'path/to/nested'));
        $manager = new JasonLewis\Basset\AssetManager($files, 'path/to', 'local');
        $file = m::mock('SplFileInfo');
        $file->shouldReceive('getRealPath')->once()->andReturn('path/to/nested/bar.css');
        $directory = m::mock('JasonLewis\Basset\Directory[recursivelyIterateDirectory]', array($files, $manager, 'path/to/nested'));
        $directory->shouldReceive('recursivelyIterateDirectory')->once()->with('path/to/nested')->andReturn(array($file));
        $collection = m::mock('JasonLewis\Basset\Collection[parseDirectoryPath]');
        $collection->shouldReceive('parseDirectoryPath')->with('path/to/nested')->andReturn($directory);
        $collection->__construct($files, $config, $manager, 'foo');
        $collection->add('bar.css');
        $assets = $collection->getAssets();
        $this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[0]);
    }


    public function testAddAssetFromWithinWorkingDirectory()
    {
        $files = $this->getFiles();
        $files->shouldReceive('exists')->twice()->with('path/to/nested/bar.css')->andReturn(true);
        $config = $this->getConfig();
        $config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
        $manager = new JasonLewis\Basset\AssetManager($files, 'path/to', 'local');
        $directory = m::mock('JasonLewis\Basset\Directory');
        $directory->shouldReceive('getPath')->twice()->andReturn('path/to/nested');
        $collection = m::mock('JasonLewis\Basset\Collection[parseDirectoryPath]');
        $collection->shouldReceive('parseDirectoryPath')->with('path/to/nested')->andReturn($directory);
        $collection->__construct($files, $config, $manager, 'foo');
        $collection->directory('path/to/nested', function($collection)
        {
            $collection->add('bar.css');
        });
        $assets = $collection->getAssets();
        $this->assertInstanceOf('JasonLewis\Basset\Asset', $assets[0]);
    }


    public function testFiltersAreAppliedToCollection()
    {
        $files = $this->getFiles();
        $files->shouldReceive('exists')->twice()->with('path/to/bar.css')->andReturn(true);
        $config = $this->getConfig();
        $config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
        $manager = new JasonLewis\Basset\AssetManager($files, 'path/to', 'local');
        $collection = new JasonLewis\Basset\Collection($files, $config, $manager, 'foo');
        $collection->apply('FooFilter');
        $collection->add('bar.css');
        $filters = $collection->getFilters();
        $this->assertCount(1, $filters);
        $this->assertArrayHasKey('FooFilter', $filters);
        $collection->processCollection();
        $assets = $collection->getAssets();
        $this->assertCount(0, $collection->getFilters());
        $this->assertCount(1, $assets[0]->getFilters());
    }


    public function testGetAllIgnoredAssets()
    {
        $files = $this->getFiles();
        $files->shouldReceive('exists')->twice()->with('path/to/bar.css')->andReturn(true);
        $files->shouldReceive('exists')->twice()->with('path/to/foo.css')->andReturn(true);
        $config = $this->getConfig();
        $config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
        $config->shouldReceive('has')->once()->with('basset::assets.foo.css')->andReturn(false);
        $manager = new JasonLewis\Basset\AssetManager($files, 'path/to', 'local');
        $collection = new JasonLewis\Basset\Collection($files, $config, $manager, 'foo');
        $collection->add('bar.css')->ignore();
        $collection->add('foo.css');
        $assets = $collection->getIgnoredAssets();
        $this->assertCount(1, $assets);
        $this->assertCount(2, $collection->getAssets());
    }


    public function testGetIgnoredAssetsByGroup()
    {
        $files = $this->getFiles();
        $files->shouldReceive('exists')->twice()->with('path/to/bar.css')->andReturn(true);
        $files->shouldReceive('exists')->twice()->with('path/to/foo.js')->andReturn(true);
        $config = $this->getConfig();
        $config->shouldReceive('has')->once()->with('basset::assets.bar.css')->andReturn(false);
        $config->shouldReceive('has')->once()->with('basset::assets.foo.js')->andReturn(false);
        $manager = m::mock('JasonLewis\Basset\AssetManager[getAbsolutePath]', array($files, 'path/to', 'local'));
        $manager->shouldReceive('getAbsolutePath')->with('path/to/bar.css')->andReturn('path/to/bar.css');
        $manager->shouldReceive('getAbsolutePath')->with('path/to/foo.js')->andReturn('path/to/foo.js');
        $collection = new JasonLewis\Basset\Collection($files, $config, $manager, 'foo');
        $collection->add('bar.css')->ignore();
        $collection->add('foo.js')->ignore();
        $assets = $collection->getIgnoredAssets('styles');
        $this->assertCount(1, $assets);
    }


    public function testCollectionIsCompiledCorrectly()
    {
        $files = $this->getFiles();
        $files->shouldReceive('exists')->twice()->with('path/to/css/example.css')->andReturn(true);
        $files->shouldReceive('exists')->twice()->with('path/to/js/example.js')->andReturn(true);
        $files->shouldReceive('getRemote')->once()->with('path/to/css/example.css')->andReturn('html { background-color: #fff; }');
        $files->shouldReceive('getRemote')->once()->with('path/to/js/example.js')->andReturn('alert("hello world")');
        $files->shouldReceive('getRemote')->once()->with('http://foo.com/bar.css')->andReturn('a { text-decoration: none; }');
        $config = $this->getConfig();
        $config->shouldReceive('has')->once()->with('basset::assets.css/example.css')->andReturn(false);
        $config->shouldReceive('has')->once()->with('basset::assets.js/example.js')->andReturn(false);
        $config->shouldReceive('has')->once()->with('basset::assets.http://foo.com/bar.css')->andReturn(false);
        $config->shouldReceive('get')->once()->with('basset::compile_remotes', false)->andReturn(true);
        $manager = m::mock('JasonLewis\Basset\AssetManager[getAbsolutePath]', array($files, 'path/to', 'local'));
        $manager->shouldReceive('getAbsolutePath')->with('path/to/css/example.css')->andReturn('path/to/css/example.css');
        $manager->shouldReceive('getAbsolutePath')->with('path/to/js/example.js')->andReturn('path/to/js/example.js');
        $manager->shouldReceive('getAbsolutePath')->with('http://foo.com/bar.css')->andReturn('http://foo.com/bar.css');
        $instantiatedFilter = m::mock('Assetic\Filter\FilterInterface');
        $instantiatedFilter->shouldReceive('filterLoad')->twice()->andReturn(null);
        $instantiatedFilter->shouldReceive('filterDump')->twice()->andReturnUsing(function($asset)
        {
            $asset->setContent(str_replace('html', 'body', $asset->getContent()));
        });
        $filter = m::mock('JasonLewis\Basset\Filter');
        $filter->shouldReceive('getFilter')->times(4)->andReturn('BodyFilter');
        $filter->shouldReceive('getGroupRestriction')->times(3)->andReturn('styles');
        $filter->shouldReceive('getEnvironments')->times(3)->andReturn(array());
        $filter->shouldReceive('instantiate')->twice()->andReturn($instantiatedFilter);
        $collection = new JasonLewis\Basset\Collection($files, $config, $manager, 'foo');
        $collection->add('css/example.css');
        $collection->add('js/example.js');
        $collection->add('http://foo.com/bar.css');
        $collection->apply($filter);
        $compiler = new JasonLewis\Basset\Compiler\StringCompiler($files, $config);
        $compiledCss = $compiler->compileStyles($collection);
        $compiledJs = $compiler->compileScripts($collection);
        $this->assertEquals('body { background-color: #fff; }', $compiledCss['path/to/css/example.css']);
        $this->assertEquals('a { text-decoration: none; }', $compiledCss['http://foo.com/bar.css']);
        $this->assertEquals('alert("hello world")', $compiledJs['path/to/js/example.js']);
    }


    protected function getStandardCollectionInstance()
    {
        $files = $this->getFiles();
        $config = $this->getConfig();
        $manager = $this->getManager();
        return new JasonLewis\Basset\Collection($files, $config, $manager, 'foo');
    }


    protected function getFiles()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }


    protected function getConfig()
    {
        return m::mock('Illuminate\Config\Repository');
    }


    protected function getManager()
    {
        return m::mock('JasonLewis\Basset\AssetManager');
    }


}