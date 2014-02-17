<?php namespace Basset\Manifest;

use Basset\Collection;
use Illuminate\Filesystem\Filesystem;

class Manifest {

  /**
   * Illuminate filesystem instance.
   *
   * @var \Illuminate\Filesystem\Filesystem
   */
  protected $files;
  /**
   * Path to the manifest.
   *
   * @var string
   */
  protected $manifestPath;
  /**
   * Collection of manifest entries.
   *
   * @var \Illuminate\Support\Collection
   */
  protected $entries;
  /**
   * Indicates if the manifest is dirty and needs saving.
   *
   * @var bool
   */
  protected $dirty = FALSE;

  /**
   * Create a new manifest instance.
   *
   * @param  \Illuminate\Filesystem\Filesystem $files
   * @param  string $manifestPath
   * @return void
   */
  public function __construct(Filesystem $files, $manifestPath)
  {
    $this->files        = $files;
    $this->manifestPath = $manifestPath;
    $this->entries      = new \Illuminate\Support\Collection;
  }

  /**
   * Make a collection entry if it does not already exist on the manifest.
   *
   * @param  string|\Basset\Collection $collection
   * @return \Basset\Manifest\Entry
   */
  public function make($collection)
  {
    $collection = $this->getCollectionNameFromInstance($collection);

    $this->dirty = TRUE;

    return $this->get($collection) ? : $this->entries[$collection] = new Entry;
  }

  /**
   * Get the collections identifier from a collection instance.
   *
   * @param  string|\Basset\Collection $collection
   * @return string
   */
  protected function getCollectionNameFromInstance($collection)
  {
    return $collection instanceof Collection ? $collection->getIdentifier() : $collection;
  }

  /**
   * Get a collection entry from the manifest or create a new entry.
   *
   * @param  string|\Basset\Collection $collection
   * @return null|\Basset\Manifest\Entry
   */
  public function get($collection)
  {
    $collection = $this->getCollectionNameFromInstance($collection);

    return isset($this->entries[$collection]) ? $this->entries[$collection] : NULL;
  }

  /**
   * Forget a collection from the repository.
   *
   * @param  string|\Basset\Collection $collection
   * @return void
   */
  public function forget($collection)
  {
    $collection = $this->getCollectionNameFromInstance($collection);

    if ($this->has($collection)) {
      $this->dirty = TRUE;

      unset($this->entries[$collection]);
    }
  }

  /**
   * Determine if the manifest has a given collection entry.
   *
   * @param  string $collection
   * @return bool
   */
  public function has($collection)
  {
    return !is_null($this->get($collection));
  }

  /**
   * Get all the entries.
   *
   * @return array
   */
  public function all()
  {
    return $this->entries;
  }

  /**
   * Loads and registers the manifest entries.
   *
   * @return void
   */
  public function load()
  {
    $path = $this->manifestPath . '/collections.json';

    if ($this->files->exists($path) and is_array($manifest = json_decode($this->files->get($path), TRUE))) {
      foreach ($manifest as $key => $entry) {
        $entry = new Entry($entry['fingerprints'], $entry['development']);

        $this->entries->put($key, $entry);
      }
    }
  }

  /**
   * Save the manifest.
   *
   * @return bool
   */
  public function save()
  {
    if ($this->dirty) {
      $path = $this->manifestPath . '/collections.json';

      $this->dirty = FALSE;

      return (bool) $this->files->put($path, $this->entries->toJson());
    }

    return FALSE;
  }

  /**
   * Delete the manifest.
   *
   * @return bool
   */
  public function delete()
  {
    if ($this->files->exists($path = $this->manifestPath . '/collections.json')) {
      return $this->files->delete($path);
    }
    else {
      return FALSE;
    }
  }

}