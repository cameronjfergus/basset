<?php

use Mockery as m;
use Basset\Filter\Filter;

class FilterTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testSettingOfFilterInstantiationArguments()
    {
        $filter = $this->getFilterInstance();

        $filter->setArguments('bar', 'baz');

        $arguments = $filter->getArguments();

        $this->assertContains('bar', $arguments);
        $this->assertContains('baz', $arguments);
    }


    public function testSettingOfFilterEnvironments()
    {
        $filter = $this->getFilterInstance();

        $filter->onEnvironment('foo');
        $this->assertContains('foo', $filter->getEnvironments());

        $filter->onEnvironments('bar', 'baz');
        $this->assertContains('bar', $filter->getEnvironments());
        $this->assertContains('baz', $filter->getEnvironments());
    }


    public function testSettingOfFilterGroupRestriction()
    {
        $filter = $this->getFilterInstance();

        $filter->onlyJavascripts();
        $this->assertEquals('javascripts', $filter->getGroupRestriction());

        $filter->onlyStylesheets();
        $this->assertEquals('stylesheets', $filter->getGroupRestriction());
    }


    public function testInstantiationOfFiltersWithNoArguments()
    {
        $filter = $this->getFilterInstance();
        $filter->shouldReceive('getClassName')->once()->andReturn('FilterStub');

        $instance = $filter->getInstance();

        $this->assertInstanceOf('FilterStub', $instance);
    }


    public function testInstantiationOfFiltersWithArguments()
    {
        $filter = $this->getFilterInstance();
        $filter->shouldReceive('getClassName')->once()->andReturn('FilterWithConstructorStub');

        $filter->setArguments('bar');

        $instance = $filter->getInstance();

        $this->assertEquals('bar', $instance->getFooBin());
    }


    public function testInstantiationOfFiltersWithBeforeFilteringCallback()
    {
        $filter = $this->getFilterInstance();
        $filter->shouldReceive('getClassName')->once()->andReturn('FilterStub');

        $tester = $this;

        $filter->beforeFiltering(function($filter) use ($tester)
        {
            $filter->setFooBin('bar');

            $tester->assertInstanceOf('FilterStub', $filter);
        });

        $instance = $filter->getInstance();

        $this->assertEquals('bar', $instance->getFooBin());
    }


    public function testInvalidMethodsAreHandledByResource()
    {
        $filter = new Filter('FooFilter');
        $filter->setResource($this->getResourceMock());
        $filter->getResource()->shouldReceive('foo')->once()->andReturn('bar');

        $this->assertEquals('bar', $filter->foo());
    }


    public function testFindingOfMissingConstructorArgsSkipsPresentArgument()
    {
        $filter = $this->getFilterInstance();
        $filter->shouldReceive('getClassName')->once()->andReturn('FilterWithConstructorStub');
        $filter->shouldReceive('getExecutableFinder')->once()->andReturn(m::mock('Symfony\Component\Process\ExecutableFinder'));

        $filter->setArguments('foo');

        $filter->findMissingConstructorArgs();

        $this->assertContains('foo', $filter->getArguments());
    }


    public function testFindingOfMissingConstructorArgsViaEnvironmentVariable()
    {
        $filter = $this->getFilterInstance();
        $filter->shouldReceive('getClassName')->once()->andReturn('FilterWithConstructorStub');
        $filter->shouldReceive('getExecutableFinder')->once()->andReturn(m::mock('Symfony\Component\Process\ExecutableFinder'));
        $filter->shouldReceive('getEnvironmentVariable')->once()->with('foo_bin')->andReturn('path/to/foo/bin');

        $filter->findMissingConstructorArgs();

        $this->assertContains('path/to/foo/bin', $filter->getArguments());
    }


    public function testFindingOfMissingConstructorArgsViaExecutableFinder()
    {
        $filter = $this->getFilterInstance();
        $filter->shouldReceive('getClassName')->once()->andReturn('FilterWithConstructorStub');
        $filter->shouldReceive('getExecutableFinder')->once()->andReturn($finder = m::mock('Symfony\Component\Process\ExecutableFinder'));

        $finder->shouldReceive('find')->once()->with('foo')->andReturn('path/to/foo/bin');

        $filter->findMissingConstructorArgs();

        $this->assertContains('path/to/foo/bin', $filter->getArguments());
    }


    public function testFindingOfMissingConstructorArgsSetsFilterNodePaths()
    {
        $filter = m::mock('Basset\Filter\Filter', array('FooFilter', array('path/to/node')))->shouldDeferMissing();
        $filter->setResource($this->getResourceMock());
        $filter->shouldReceive('getClassName')->once()->andReturn('FilterWithConstructorStub');
        $filter->shouldReceive('getExecutableFinder')->once()->andReturn($finder = m::mock('Symfony\Component\Process\ExecutableFinder'));

        $filter->shouldReceive('getEnvironmentVariable')->once()->with('foo_bin')->andReturn('path/to/foo/bin');

        $filter->findMissingConstructorArgs();

        $this->assertContains(array('path/to/node'), $filter->getArguments());
    }


    public function testFindingOfMissingConstructorArgsIgnoresFilterForInvalidExecutables()
    {
        $filter = $this->getFilterInstance();
        $filter->shouldReceive('getClassName')->once()->andReturn('FilterWithConstructorStub');
        $filter->shouldReceive('getExecutableFinder')->once()->andReturn($finder = m::mock('Symfony\Component\Process\ExecutableFinder'));

        $finder->shouldReceive('find')->once()->with('foo')->andReturn(false);

        $filter->findMissingConstructorArgs();

        $this->assertTrue($filter->isIgnored());
    }


    public function testFindingOfMissingConstructorArgsIsSkippedWhenNoConstructorPresent()
    {
        $filter = $this->getFilterInstance();
        $filter->shouldReceive('getClassName')->once()->andReturn('FilterStub');

        $filter->findMissingConstructorArgs();
    }


    protected function getFilterInstance()
    {
        $mock = m::mock('Basset\Filter\Filter', array('FooFilter'))->shouldDeferMissing();
        $mock->setResource($this->getResourceMock());

        return $mock;
    }


    protected function getResourceMock()
    {
        return m::mock('Basset\Filter\FilterableInterface');
    }


}


class FilterStub {

    protected $fooBin;

    public function setFooBin($fooBin)
    {
        $this->fooBin = $fooBin;
    }

    public function getFooBin()
    {
        return $this->fooBin;
    }

}


class FilterWithConstructorStub {

    protected $fooBin;

    public function __construct($fooBin, $nodePaths = array())
    {
        $this->fooBin = $fooBin;
    }

    public function getFooBin()
    {
        return $this->fooBin;
    }

}