<?php namespace Basset;

use FilesystemIterator;
use Basset\Manifest\Repository;
use Illuminate\Filesystem\Filesystem;

class BuildCleaner {

    /**
     * Create a new build cleaner instance.
     *
     * @param  Basset\Manifest\Repository  $manifest
     * @param  Illuminate\Filesystem\Filesystem  $files
     * @param  string  $buildPath
     * @return void
     */
    public function __construct(Repository $manifest, Filesystem $files, $buildPath)
    {
        $this->manifest = $manifest;
        $this->files = $files;
        $this->buildPath = $buildPath;
    }

    /**
     * Clean all the collections in the manifest.
     *
     * @return void
     */
    public function clean()
    {
        foreach ($this->manifest->getManifest() as $collection => $entry)
        {
            $this->performCleanup($collection, $entry['fingerprint']);
        }
    }

    /**
     * Perform the cleanup on the collection and its fingerprints.
     *
     * @param  string  $collection
     * @param  array  $fingerprints
     * @return void
     */
    protected function performCleanup($collection, $fingerprints)
    {
        foreach ($this->getFilesystemIterator($this->buildPath) as $file)
        {
            $name = $file->getFilename();

            if (str_is("{$collection}-*.css", $name) and ! str_is("{$collection}-{$fingerprints['styles']}.css", $name))
            {
                $this->files->delete($file->getPathname());
            }

            if (str_is("{$collection}-*.js", $name) and ! str_is("{$collection}-{$fingerprints['scripts']}.js", $name))
            {
                $this->files->delete($file->getPathname());
            }
        }
    }

    /**
     * Get a filesystem iterator instance.
     *
     * @param  string  $path
     * @return FilesystemIterator
     */
    public function getFilesystemIterator($path)
    {
        return new FilesystemIterator($path);
    }

}