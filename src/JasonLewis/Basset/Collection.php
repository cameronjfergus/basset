<?php namespace JasonLewis\Basset;

use Closure;
use RuntimeException;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use JasonLewis\Basset\Compiler\StringCompiler;

class Collection implements FilterableInterface {

    /**
     * Name of the collection.
     *
     * @var string
     */
    protected $name;

    /**
     * Illuminate filesystem instance.
     *
     * @var Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Illuminate config repository instance.
     *
     * @var Illuminate\Config\Repository
     */
    protected $config;

    /**
     * Basset asset manager instance.
     *
     * @var JasonLewis\Basset\AssetManager
     */
    protected $manager;

    /**
     * Array of assets.
     *
     * @var array
     */
    protected $assets = array();

    /**
     * Array of directories that have been required.
     *
     * @var array
     */
    protected $directories = array();

    /**
     * Array of filters.
     *
     * @var array
     */
    protected $filters = array();

    /**
     * Collection working directory.
     *
     * @var JasonLewis\Basset\Directory
     */
    protected $workingDirectory;

    /**
     * Create a new collection instance.
     *
     * @param  Illuminate\Filesystem\Filesystem  $files
     * @param  Illuminate\Config\Repository  $config
     * @param  JasonLewis\Basset\AssetManager  $manager
     * @param  string  $name
     * @return void
     */
    public function __construct(Filesystem $files, Repository $config, AssetManager $manager, $name)
    {
        $this->files = $files;
        $this->config = $config;
        $this->manager = $manager;
        $this->name = $name;
    }

    /**
     * Get the name of the collection.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add an asset to the collection.
     *
     * @param  string  $name
     * @return JasonLewis\Basset\Asset
     */
    public function add($name)
    {
        $assetPath = null;

        // Determine if the asset has been given an alias. We'll use the alias as the name of
        // the asset.
        if ($this->config->has("basset::assets.{$name}"))
        {
            $name = $this->config->get("basset::assets.{$name}");
        }

        // Determine if the asset is a remotely hosted asset. We can check that by filtering
        // the variable as a valid URL.
        if (filter_var($name, FILTER_VALIDATE_URL))
        {
            $assetPath = $name;
        }

        // If the name of the asset is prefixed with 'path: ' then the absolute path to the asset
        // is being provided. This is best avoided as assets should always be within the public
        // directory.
        elseif (starts_with($name, 'path: '))
        {
            $assetPath = substr($name, 6);
        }

        // Determine if the asset exists within the current working directory.
        elseif ($this->workingDirectory instanceof Directory and $this->files->exists($this->workingDirectory->getPath().'/'.$name))
        {
            $assetPath = $this->workingDirectory->getPath().'/'.$name;
        }

        // Determine if the asset exists within the public directory.
        elseif ($this->manager->find($name))
        {
            $assetPath = $this->manager->path($name);
        }

        // Lastly we'll attempt to locate the asset by spinning through all of the named directories.
        // If the asset cannot be found then we'll make no attempt to continue further.
        else
        {
            foreach ($this->config->get('basset::directories') as $directoryName => $directoryPath)
            {
                $directory = $this->parseDirectoryPath($directoryPath);

                if ( ! $directory instanceof Directory)
                {
                    continue;
                }

                // Recursively spin through each directory. We're simply looking for a file that has
                // the same ending as the name of the file. Once we've found it we'll bail out of
                // both loops.
                foreach ($directory->recursivelyIterateDirectory($directory->getPath()) as $file)
                {
                    $filePath = $file->getRealPath();

                    if (ends_with($this->normalizePath($filePath), $name))
                    {
                        $assetPath = $filePath;

                        break 2;
                    }
                }
            }
        }

        if ( ! is_null($assetPath))
        {
            $asset = $this->manager->make($assetPath);

            if ($asset->isRemote() or $this->files->exists($assetPath))
            {
                return $this->assets[] = $asset;
            }
        }

        // To avoid nasty errors being thrown when assets don't exist yet due to a package that is
        // yet to be published we'l return a dummy asset instance so that methods are still chainable.
        return new Asset($this->files, null, null, null);
    }

    /**
     * Change the working directory.
     *
     * @param  string  $path
     * @param  Closure  $callback
     * @return Basset\Collection
     */
    public function directory($path, Closure $callback)
    {
        try
        {
            $this->workingDirectory = $this->parseDirectoryPath($path);
        }
        catch (RuntimeException $error)
        {
            return $this;
        }

        // Once we've set the working directory we'll fire the callback so that any added assets
        // are relative to the working directory. After the callback we can revert the working
        // directory.
        call_user_func($callback, $this);

        $this->workingDirectory = null;

        return $this;
    }

    /**
     * Require all assets within a directory.
     *
     * @param  string  $path
     * @return JasonLewis\Basset\Directory
     */
    public function requireDirectory($path = null)
    {
        try
        {
            $directory = $this->parseRequirePath($path);
        }
        catch (RuntimeException $error)
        {
            return new Directory($this->files, $this->manager, null);
        }

        return $this->directories[] = $directory->requireDirectory();
    }

    /**
     * Require all assets within a directory tree.
     *
     * @param  string  $path
     * @return JasonLewis\Basset\Directory
     */
    public function requireTree($path = null)
    {
        try
        {
            $directory = $this->parseRequirePath($path);
        }
        catch (RuntimeException $error)
        {
            return new Directory($this->files, $this->manager, null);
        }

        return $this->directories[] = $directory->requireTree();
    }

    /**
     * Parse a require directory or tree path and return a directory instance.
     *
     * @param  string  $path
     * @return JasonLewis\Basset\Directory
     */
    protected function parseRequirePath($path)
    {
        // If no path was given then we'll check if we're working within a directory. If not then the
        // method is not being used correctly.
        $directory = null;

        if (is_null($path))
        {
            if ($this->workingDirectory instanceof Directory)
            {
                $directory = $this->workingDirectory;
            }
        }
        else
        {
            $directory = $this->parseDirectoryPath($path);
        }

        if ( ! $directory instanceof Directory)
        {
            throw new RuntimeException("Invalid path or working directory supplied.");
        }

        return $directory;
    }

    /**
     * Parse a directory path and return a directory instance.
     *
     * @param  string  $directory
     * @return JasonLewis\Basset\Directory
     */
    public function parseDirectoryPath($path)
    {
        // Determine if the directory has been given an alias. We'll use the alias as the path to
        // the directory.
        if (starts_with($path, 'name: '))
        {
            $name = substr($path, 6);

            if ($this->config->has("basset::directories.{$name}"))
            {
                $path = $this->config->get("basset::directories.{$name}");
            }
        }

        // If the path to the directory is prefixed with 'path: ' then the absolute path to the
        // directory is being provided.
        if (starts_with($path, 'path: '))
        {
            $path = substr($path, 6);
        }

        // Lastly we'll prefix the directory path with the path to the public directory.
        else
        {
            $path = $this->manager->path($path);
        }

        if ($this->files->exists($path))
        {
            return new Directory($this->files, $this->manager, $path);
        }
    }

    /**
     * Process the collection by retrieving all assets for each directory and then applying
     * any collection filters to every asset.
     *
     * @return void
     */
    public function processCollection()
    {
        foreach ($this->directories as $directory)
        {
            $directory->processFilters();

            $this->assets = array_merge($this->assets, $directory->getAssets());
        }

        // If there are filters applied to the collection then these filters must be applied tp
        // each asset within the collection. Spin through all the assets and apply the filter!
        if ( ! empty($this->filters))
        {
            foreach ($this->assets as $key => $asset)
            {
                foreach ($this->filters as $filter)
                {
                    $this->assets[$key]->apply($filter);
                }
            }

            // After applying all the filters to all the assets we'll reset the filters array.
            $this->filters = array();
        }
    }

    /**
     * Normalize a give path.
     *
     * @param  string  $path
     * @return string
     */
    protected function normalizePath($path)
    {
        return str_replace('\\', '/', $path);
    }

    /**
     * Apply a filter to the entire collection.
     *
     * @param  string  $filter
     * @param  Closure  $callback
     * @return JasonLewis\Basset\Filter
     */
    public function apply($filter, Closure $callback = null)
    {
        // If the supplied filter is already a Filter instance then we'll set the filter
        // directly on the asset.
        if ($filter instanceof Filter)
        {
            return $this->filters[$filter->getFilter()] = $filter;
        }

        $filter = new Filter($filter, $this);

        if (is_callable($callback))
        {
            call_user_func($callback, $filter);
        }

        return $this->filters[$filter->getFilter()] = $filter;
    }

    /**
     * Get an array of assets filtered by a group.
     *
     * @param  string  $group
     * @return array
     */
    public function getAssets($group = null)
    {
        $assets = array();

        foreach ($this->assets as $asset)
        {
            if (is_null($group) or $asset->{'is'.ucfirst(str_singular($group))}())
            {
                $assets[] = $asset;
            }
        }

        return $assets;
    }

    /**
     * Get an array of ignored assets filtered by a group.
     *
     * @param  string  $group
     * @return array
     */
    public function getIgnoredAssets($group = null)
    {
        $assets = array();

        foreach ($this->assets as $asset)
        {
            if ($asset->isIgnored() and (is_null($group) or $asset->{'is'.ucfirst(str_singular($group))}()))
            {
                $assets[] = $asset;
            }
        }

        return $assets;
    }

    /**
     * Get the applied filters.
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

}