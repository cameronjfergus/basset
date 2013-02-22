<?php namespace JasonLewis\Basset;

use Assetic\Asset\StringAsset;
use Assetic\Filter\FilterInterface;
use Illuminate\Filesystem\Filesystem;

class Asset implements FilterableInterface {

	/**
	 * Illuminate filesystem instance.
	 * 
	 * @var Illuminate\Filesystem\Filesystem
	 */
	protected $files;

	/**
	 * Absolute path to the asset.
	 * 
	 * @var string
	 */
	protected $absolutePath;

	/**
	 * Relative path to the asset.
	 * 
	 * @var string
	 */
	protected $relativePath;

	/**
	 * Application working environment.
	 * 
	 * @var string
	 */
	protected $environment;

	/**
	 * Array of filters.
	 * 
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Indicates if the asset is to be ignored from compiling.
	 * 
	 * @var bool
	 */
	protected $ignored = false;

	/**
	 * Create a new asset instance.
	 * 
	 * @param  Illuminate\Filesystem\Filesystem  $files
	 * @param  string  $absolutePath
	 * @param  string  $relativePath
	 * @param  string  $environment
	 * @return void
	 */
	public function __construct(Filesystem $files, $absolutePath, $relativePath, $environment)
	{
		$this->files = $files;
		$this->absolutePath = $absolutePath;
		$this->relativePath = $relativePath;
		$this->environment = $environment;
	}

	/**
	 * Get the absolute path to the asset.
	 * 
	 * @return string
	 */
	public function getAbsolutePath()
	{
		return $this->absolutePath;
	}

	/**
	 * Get the relative path to the asset.
	 * 
	 * @return string
	 */
	public function getRelativePath()
	{
		return $this->relativePath;
	}

	/**
	 * Determine if asset is a script.
	 * 
	 * @return bool
	 */
	public function isScript()
	{
		return in_array(pathinfo($this->absolutePath, PATHINFO_EXTENSION), array('js', 'coffee'));
	}

	/**
	 * Determine if asset is a style.
	 * 
	 * @return bool
	 */
	public function isStyle()
	{
		return ! $this->isScript();
	}

	/**
	 * Determine if asset is remotely hosted.
	 * 
	 * @return bool
	 */
	public function isRemote()
	{
		return (bool) filter_var($this->absolutePath, FILTER_VALIDATE_URL);
	}

	/**
	 * Sets the asset to be ignored.
	 * 
	 * @return JasonLewis\Basset\Asset
	 */
	public function ignore()
	{
		$this->ignored = true;

		return $this;
	}

	/**
	 * Determine if the asset is ignored.
	 * 
	 * @return bool
	 */
	public function isIgnored()
	{
		return $this->ignored;
	}

	/**
	 * Prepare the assets filters by removing those that have been restricted.
	 * 
	 * @return void
	 */
	public function prepareFilters()
	{
		foreach ($this->filters as $key => $filter)
		{
			// If there is a group restriction on the filter and the assets group is not that being
			// restricted then we'll remove the filter from the asset.
			$groupRestriction = $filter->getGroupRestriction();

			if ($groupRestriction and ! $this->{'is'.ucfirst(str_singular($groupRestriction))}())
			{
				unset($this->filters[$key]);
			}

			// If the filter is being restricted to certain environments we'll make sure the application
			// is running within one of the specified environments.
			$possibleEnvironments = $filter->getEnvironments();

			if ($possibleEnvironments and ! in_array($this->environment, $possibleEnvironments))
			{
				unset($this->filters[$key]);
			}
		}
	}

	/**
	 * Apply a filter to the asset.
	 * 
	 * @param  string|Filter  $filter
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
	 * Get the asset contents.
	 * 
	 * @return string
	 */
	public function getContent()
	{
		return $this->files->getRemote($this->absolutePath);
	}

	/**
	 * Compile the asset.
	 * 
	 * @return string
	 */
	public function compile()
	{
		// Before we begin to compile we'll prepare the filters by removing those that are not
		// to be applied to this asset.
		$this->prepareFilters();

		$appliedFilters = array();

		foreach ($this->filters as $filter)
		{
			// Attempt to instantiate each filter. If we can get an instance we'll add the filter
			// to the array of filters.
			$filterInstance = $filter->instantiate();

			if ($filterInstance instanceof FilterInterface)
			{
				$appliedFilters[] = $filterInstance;
			}
		}

		$contents = $this->getContent();

		$asset = new StringAsset($contents, $appliedFilters, dirname($this->absolutePath), basename($this->absolutePath));

		return $asset->dump();
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