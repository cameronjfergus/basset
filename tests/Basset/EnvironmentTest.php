<?php

use Mockery as m;
use Basset\Environment;

class BassetTest extends PHPUnit_Framework_TestCase {


    public function tearDown()
    {
        m::close();
    }


    public function testCollectionInstanceIsCreated()
    {
        $env = $this->getEnvInstance();

        $this->assertInstanceOf('Basset\Collection', $env->collection('foo'));

        $env['bar'] = function(){};
        $this->assertInstanceOf('Basset\Collection', $env['bar']);
    }


    public function testCollectionCallbackIsFiredUponCreation()
    {
        $env = $this->getEnvInstance();

        $fired = false;

        $env->collection('foo', function($collection) use (&$fired)
        {
            $fired = true;
        });

        $this->assertTrue($fired);
    }


    public function testGetAllCollections()
    {
        $env = $this->getEnvInstance();

        $env->collection('foo');
        $env->collection('bar');

        $this->assertCount(2, $env->getCollections());
        $this->assertNull($env['baz']);
    }


    public function testCheckingCollectionExistence()
    {
        $env = $this->getEnvInstance();

        $env->collection('foo');

        $this->assertTrue($env->hasCollection('foo'));
        $this->assertTrue(isset($env['foo']));
        $this->assertFalse($env->hasCollection('bar'));
    }


    protected function getEnvInstance()
    {
        $files = m::mock('Illuminate\Filesystem\Filesystem');
        $config = m::mock('Illuminate\Config\Repository');
        $factory = m::mock('Basset\Factory\FactoryManager');
        $finder = m::mock('Basset\AssetFinder');

        return new Environment($files, $config, $factory, $finder);
    }


}