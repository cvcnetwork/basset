<?php namespace Basset;

use Basset\Filter\Filterable;
use InvalidArgumentException;
use Assetic\Asset\StringAsset;
use Basset\Factory\FactoryManager;
use Assetic\Filter\FilterInterface;
use Illuminate\Filesystem\Filesystem;


class Asset extends Filterable {

  /**
   * Illuminate filesystem instance.
   *
   * @var \Illuminate\Filesystem\Filesystem
   */
  protected $files;
  /**
   * Basset factory manager instance.
   *
   * @var \Basset\Factory\FactoryManager
   */
  protected $factory;
  /**
   * Application environment.
   *
   * @var string
   */
  protected $appEnvironment;
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
   * Indicates if the asset is to be served raw.
   *
   * @var bool
   */
  protected $raw = FALSE;
  /**
   * Order of the asset.
   *
   * @var int
   */
  protected $order;
  /**
   * Assets cached last modified time.
   *
   * @var int
   */
  protected $lastModified;
  /**
   * Group the asset belongs to, either stylesheets or javascripts.
   *
   * @var string
   */
  protected $group;
  /**
   * Array of allowed asset extensions.
   *
   * @var array
   */
  protected $allowedExtensions = array(
    'stylesheets' => array('css', 'sass', 'scss', 'less', 'styl', 'roo', 'gss'),
    'javascripts' => array('js', 'coffee', 'dart', 'ts', 'hbs')
  );

  /**
   * Create a new asset instance.
   *
   * @param  \Illuminate\Filesystem\Filesystem $files
   * @param  \Basset\Factory\FactoryManager $factory
   * @param  string $appEnvironment
   * @param  string $absolutePath
   * @param  string $relativePath
   * @return void
   */
  public function __construct(Filesystem $files, FactoryManager $factory, $appEnvironment, $absolutePath, $relativePath)
  {
    parent::__construct();

    $this->files          = $files;
    $this->factory        = $factory;
    $this->appEnvironment = $appEnvironment;
    $this->absolutePath   = $absolutePath;
    $this->relativePath   = $relativePath;
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
   * Get the build path to the asset.
   *
   * @return string
   */
  public function getBuildPath()
  {
    $path = pathinfo($this->relativePath);

    $fingerprint = md5($this->filters->map(function ($f) {
      return $f->getFilter();
    })->toJson() . $this->getLastModified());

    return "{$path['dirname']}/{$path['filename']}-{$fingerprint}.{$this->getBuildExtension()}";
  }

  /**
   * Get the last modified time of the asset.
   *
   * @return int
   */
  public function getLastModified()
  {
    if ($this->lastModified) {
      return $this->lastModified;
    }

    return $this->lastModified = $this->isRemote() ? NULL : $this->files->lastModified($this->absolutePath);
  }

  /**
   * Determine if asset is remotely hosted.
   *
   * @return bool
   */
  public function isRemote()
  {
    return starts_with($this->absolutePath, '//') or (bool) filter_var($this->absolutePath, FILTER_VALIDATE_URL);
  }

  /**
   * Get the build extension of the asset.
   *
   * @return string
   */
  public function getBuildExtension()
  {
    return $this->isJavascript() ? 'js' : 'css';
  }

  /**
   * Determine if asset is a javascript.
   *
   * @return bool
   */
  public function isJavascript()
  {
    return $this->getGroup() == 'javascripts';
  }

  /**
   * Get the assets group.
   *
   * @return string
   */
  public function getGroup()
  {
    if ($this->group) {
      return $this->group;
    }

    return $this->group = $this->detectGroupFromExtension() ? : $this->detectGroupFromContentType();
  }

  /**
   * Set the assets group.
   *
   * @param  string $group
   * @return \Basset\Asset
   */
  public function setGroup($group)
  {
    $this->group = $group;

    return $this;
  }

  /**
   * Detect group from the assets extension.
   *
   * @return string
   */
  protected function detectGroupFromExtension()
  {
    $extension = pathinfo($this->absolutePath, PATHINFO_EXTENSION);

    foreach (array('stylesheets', 'javascripts') as $group) {
      if (in_array($extension, $this->allowedExtensions[$group])) {
        return $group;
      }
    }
  }

  /**
   * Detect the group from the content type using cURL.
   *
   * @return null|string
   */
  protected function detectGroupFromContentType()
  {
    if (extension_loaded('curl')) {
      $this->getLogger()
        ->warning('Attempting to determine asset group using cURL. This may have a considerable effect on application speed.');

      $handler = curl_init($this->absolutePath);

      curl_setopt($handler, CURLOPT_RETURNTRANSFER, TRUE);
      curl_setopt($handler, CURLOPT_FOLLOWLOCATION, TRUE);
      curl_setopt($handler, CURLOPT_HEADER, TRUE);
      curl_setopt($handler, CURLOPT_NOBODY, TRUE);
      curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, FALSE);

      curl_exec($handler);

      if (!curl_errno($handler)) {
        $contentType = curl_getinfo($handler, CURLINFO_CONTENT_TYPE);

        return starts_with($contentType, 'text/css') ? 'stylesheets' : 'javascripts';
      }
    }
  }

  /**
   * Determine if asset is a stylesheet.
   *
   * @return bool
   */
  public function isStylesheet()
  {
    return $this->getGroup() == 'stylesheets';
  }

  /**
   * Alias for \Basset\Asset::setOrder(1)
   *
   * @return \Basset\Asset
   */
  public function first()
  {
    return $this->setOrder(1);
  }

  /**
   * Alias for \Basset\Asset::setOrder(2)
   *
   * @return \Basset\Asset
   */
  public function second()
  {
    return $this->setOrder(2);
  }

  /**
   * Alias for \Basset\Asset::setOrder(3)
   *
   * @return \Basset\Asset
   */
  public function third()
  {
    return $this->setOrder(3);
  }

  /**
   * Alias for \Basset\Asset::setOrder()
   *
   * @param  int $order
   * @return \Basset\Asset
   */
  public function order($order)
  {
    return $this->setOrder($order);
  }

  /**
   * Get the assets order.
   *
   * @return int|null
   */
  public function getOrder()
  {
    return $this->order;
  }

  /**
   * Set the order of the outputted asset.
   *
   * @param  int $order
   * @return \Basset\Asset
   */
  public function setOrder($order)
  {
    $this->order = $order;

    return $this;
  }

  /**
   * Sets the asset to be served raw when the application is running in a given environment.
   *
   * @param  string|array $environment
   * @return \Basset\Asset
   */
  public function rawOnEnvironment()
  {
    $environments = array_flatten(func_get_args());

    if (in_array($this->appEnvironment, $environments)) {
      return $this->raw();
    }

    return $this;
  }

  /**
   * A raw asset is just excluded from the build process.
   *
   * @return \Basset\Asset
   */
  public function raw()
  {
    $this->raw = TRUE;

    return $this;
  }

  /**
   * Determines if the asset is to be served raw.
   *
   * @return bool
   */
  public function isRaw()
  {
    return $this->raw;
  }

  /**
   * Build the asset.
   *
   * @param  bool $production
   * @return string
   */
  public function build($production = FALSE)
  {
    $filters = $this->prepareFilters($production);

    $asset = new StringAsset($this->getContent(), $filters->all(), dirname($this->absolutePath), basename($this->absolutePath));

    return $asset->dump();
  }

  /**
   * Prepare the filters applied to the asset.
   *
   * MODIFIED: https://github.com/laravel/framework/blob/4.1/src/Illuminate/Foundation/changes.json
   * Because: -> {"message": "Pass keys to 'map' method on Collection.", "backport": null} (Seems like it doesn't return keys, which makes our test fail)
   * This code does the job, but I would like it to be better.
   * @param  bool $production
   * @return \Illuminate\Support\Collection
   */
  public function prepareFilters($production = FALSE)
  {
    $preparedFilters = array();
    $filters         = $this->filters->all();
    foreach ($filters as $filter) {
      unset($input);
      $filter->setProduction($production);
      $input = $filter->getInstance();
      if (isset($input)) {
        $preparedFilters[$filter->getFilter()] = $input;
      }
    };
    return (new \Illuminate\Support\Collection($preparedFilters));
  }

  /**
   * Get the asset contents.
   *
   * @return string
   */
  public function getContent()
  {
    if ($this->files->exists($this->absolutePath)) {
      return ($this->files->get($this->absolutePath));
    }
    else {
      return NULL;
    }
  }
}