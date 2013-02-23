<?php namespace JasonLewis\Basset;

use Illuminate\Filesystem\Filesystem;

class AssetFactory {

    /**
     * Illuminate filesystem instance.
     *
     * @var Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Path to the public directory.
     *
     * @var string
     */
    protected $publicPath;

    /**
     * Application working environment.
     *
     * @var string
     */
    protected $workingEnvironment;

    /**
     * Create a new asset factory instance.
     *
     * @param  Illuminate\Filesystem\Filesystem  $files
     * @param  string  $publicPath
     * @param  string  $workingEnvironment
     * @return void
     */
    public function __construct(Filesystem $files, FilterFactory $filterFactory, $publicPath, $workingEnvironment)
    {
        $this->files = $files;
        $this->filterFactory = $filterFactory;
        $this->publicPath = $publicPath;
        $this->workingEnvironment = $workingEnvironment;
    }

    /**
     * Make a new asset instance, resolving the absolute and relative paths.
     *
     * @param  string  $path
     * @return JasonLewis\Basset\Asset
     */
    public function make($path)
    {
        $absolutePath = $this->getAbsolutePath($path);

        $relativePath = trim(str_replace(array(realpath($this->publicPath), '\\'), array('', '/'), $absolutePath), '/');

        return new Asset($this->files, $this->filterFactory, $absolutePath, $relativePath, $this->workingEnvironment);
    }

    /**
     * Determine if an asset exists relative from the public directory.
     *
     * @param  string  $path
     * @return bool
     */
    public function find($path)
    {
        return $this->files->exists($this->publicPath.'/'.$path);
    }

    /**
     * Get the absolute path to an asset relative to the public directory.
     *
     * @param  string  $path
     * @return string
     */
    public function path($path)
    {
        return $this->publicPath.'/'.$path;
    }

    /**
     * Get the absolute path to an asset.
     *
     * @param  string  $path
     * @return string
     */
    public function getAbsolutePath($path)
    {
        return filter_var($path, FILTER_VALIDATE_URL) ? $path : realpath($path);
    }

}