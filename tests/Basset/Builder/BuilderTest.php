<?php

use Mockery as m;

class BuilderTest extends PHPUnit_Framework_TestCase {


  public function tearDown()
  {
    m::close();
  }

  public function setUp()
  {
    $this->files = m::mock('Illuminate\Filesystem\Filesystem');
    $this->files->shouldReceive('exists')->once()->with('foo')->andReturn(TRUE);
    $this->manifest   = m::mock('Basset\Manifest\Manifest');
    $this->collection = m::mock('Basset\Collection');
    $this->builder    = new Basset\Builder\Builder($this->files, $this->manifest, 'foo');
  }

  public function testBuilderChecksForBuildPathAndMakesDirectoryIfItDoesNotExist()
  {
    $this->files->shouldReceive('exists')->once()->with('foo')->andReturn(FALSE);
    $this->files->shouldReceive('makeDirectory')->once()->with('foo')->andReturn(TRUE);

    $builder = new Basset\Builder\Builder($this->files, $this->manifest, 'foo');
  }

  /**
   * @expectedException Basset\Exceptions\BuildNotRequiredException
   */
  public function testBuildingEmptyProductionCollectionThrowsBuildNotRequiredException()
  {
    $collection = m::mock('Basset\Collection');
    $collection->shouldReceive('getAssetsWithoutRaw')->once()->with('stylesheets')
      ->andReturn(new Illuminate\Support\Collection);
    $collection->shouldReceive('getIdentifier')->once()->andReturn('foo');

    $this->manifest->shouldReceive('make')->once()->with('foo')->andReturn($entry = m::mock('Basset\Manifest\Entry'));
    $entry->shouldReceive('resetProductionFingerprint')->once()->with('stylesheets');

    $this->builder->buildAsProduction($collection, 'stylesheets');
  }

  public function testBuildingProductionCollectionWritesToFilesystemAndSetsProductionFingerprint()
  {
    $this->collection->shouldReceive('getAssetsWithoutRaw')->once()->with('stylesheets')
      ->andReturn(new Illuminate\Support\Collection(array(
      $asset = m::mock('Basset\Asset')
    )));
    $asset->shouldReceive('build')->once()->andReturn('body { }');

    $this->collection->shouldReceive('getIdentifier')->once()->andReturn('foo');
    $this->collection->shouldReceive('getExtension')->once()->with('stylesheets')->andReturn('css');

    $fingerprint = 'foo-' . md5('body { }') . '.css';

    $this->manifest->shouldReceive('make')->once()->with('foo')->andReturn($entry = m::mock('Basset\Manifest\Entry'));
    $entry->shouldReceive('getProductionFingerprint')->with('stylesheets')->andReturn(NULL);
    $entry->shouldReceive('setProductionFingerprint')->with('stylesheets',
      m::on(function ($fingerprint) {
        if (preg_match('/[0-9]{8}_[0-9]{6}\/[0-9]{8}_[0-9]{6}-foo-[a-z0-9]*\.css/', $fingerprint)) {
          return TRUE;
        }
        return FALSE;
      }));

    $this->files->shouldReceive('put')->once()->with(m::on(function ($fingerprint) {
      if (preg_match('/foo\/[0-9]{8}_[0-9]{6}\/[0-9]{8}_[0-9]{6}-foo-[a-z0-9]*\.css/', $fingerprint)) {
        return TRUE;
      }
      return FALSE;
    }), 'body { }');
    $this->files->shouldReceive('isDirectory')->andReturn(TRUE);

    $this->builder->buildAsProduction($this->collection, 'stylesheets');
  }

  public function testBuildingProductionCollectionWithForce()
  {
    $this->collection->shouldReceive('getAssetsWithoutRaw')->once()->with('stylesheets')
      ->andReturn(new Illuminate\Support\Collection(array(
      $asset = m::mock('Basset\Asset')
    )));
    $asset->shouldReceive('build')->once()->andReturn('body { }');

    $this->collection->shouldReceive('getIdentifier')->once()->andReturn('foo');
    $this->collection->shouldReceive('getExtension')->once()->with('stylesheets')->andReturn('css');

    $fingerprint = 'foo-' . md5('body { }') . '.css';

    $this->manifest->shouldReceive('make')->once()->with('foo')->andReturn($entry = m::mock('Basset\Manifest\Entry'));
    $entry->shouldReceive('getProductionFingerprint')->with('stylesheets')->andReturn($fingerprint);
    $entry->shouldReceive('setProductionFingerprint')->with('stylesheets',
      m::on(function ($fingerprint) {
        if (preg_match('/[0-9]{8}_[0-9]{6}\/[0-9]{8}_[0-9]{6}-foo-[a-z0-9]*\.css/', $fingerprint)) {
          return TRUE;
        }
        return FALSE;
      }));

    $this->files->shouldReceive('put')->once()->with(m::on(function ($fingerprint) {
      if (preg_match('/foo\/[0-9]{8}_[0-9]{6}\/[0-9]{8}_[0-9]{6}-foo-[a-z0-9]*\.css/', $fingerprint)) {
        return TRUE;
      }
      return FALSE;
    }), 'body { }');
    $this->files->shouldReceive('isDirectory')->andReturn(TRUE);

    $this->builder->setForce(TRUE);
    $this->builder->buildAsProduction($this->collection, 'stylesheets');
  }

  public function testBuildingProductionCollectionWithGzip()
  {
    $this->collection->shouldReceive('getAssetsWithoutRaw')->once()->with('stylesheets')
      ->andReturn(new Illuminate\Support\Collection(array(
      $asset = m::mock('Basset\Asset')
    )));
    $asset->shouldReceive('build')->once()->andReturn('body { }');

    $this->collection->shouldReceive('getIdentifier')->once()->andReturn('foo');
    $this->collection->shouldReceive('getExtension')->once()->with('stylesheets')->andReturn('css');

    $fingerprint = 'foo-' . md5('body { }') . '.css';

    $this->manifest->shouldReceive('make')->once()->with('foo')->andReturn($entry = m::mock('Basset\Manifest\Entry'));
    $entry->shouldReceive('getProductionFingerprint')->with('stylesheets')->andReturn(NULL);
    $entry->shouldReceive('setProductionFingerprint')->with('stylesheets',
      m::on(function ($fingerprint) {
        if (preg_match('/[0-9]{8}_[0-9]{6}\/[0-9]{8}_[0-9]{6}-foo-[a-z0-9]*\.css/', $fingerprint)) {
          return TRUE;
        }
        return FALSE;
      }));

    $this->files->shouldReceive('put')->once()->with(m::on(function ($fingerprint) {
      if (preg_match('/foo\/[0-9]{8}_[0-9]{6}\/[0-9]{8}_[0-9]{6}-foo-[a-z0-9]*\.css/', $fingerprint)) {
        return TRUE;
      }
      return FALSE;
    }), gzencode('body { }', 9));
    $this->files->shouldReceive('isDirectory')->andReturn(TRUE);

    $this->builder->setGzip(TRUE);
    $this->builder->buildAsProduction($this->collection, 'stylesheets');
  }

  /**
   * @expectedException Basset\Exceptions\BuildNotRequiredException
   */
  public function testBuildingDevelopmentCollectionWithNoAssetsThrowsBuildNotRequiredException()
  {
    $this->collection->shouldReceive('getAssetsWithoutRaw')->once()->with('stylesheets')
      ->andReturn(new Illuminate\Support\Collection);
    $this->collection->shouldReceive('getIdentifier')->once()->andReturn('foo');

    $this->manifest->shouldReceive('make')->once()->with('foo')->andReturn($entry = m::mock('Basset\Manifest\Entry'));
    $entry->shouldReceive('hasDevelopmentAssets')->once()->with('stylesheets')->andReturn(FALSE);
    $entry->shouldReceive('resetDevelopmentAssets')->once()->with('stylesheets');

    $this->builder->buildAsDevelopment($this->collection, 'stylesheets');
  }

  /**
   * @expectedException Basset\Exceptions\BuildNotRequiredException
   */
  public function testBuildingDevelopmentCollectionWithAssetsThatAreAlreadyBuiltThrowsBuildNotRequiredException()
  {
    $this->collection->shouldReceive('getAssetsWithoutRaw')->once()->with('stylesheets')
      ->andReturn(new Illuminate\Support\Collection(array(
      $assets[] = m::mock('Basset\Asset'),
      $assets[] = m::mock('Basset\Asset')
    )));
    $this->collection->shouldReceive('getIdentifier')->once()->andReturn('foo');

    $assets[0]->shouldReceive('getRelativePath')->once()->andReturn('bar/baz.css');
    $assets[0]->shouldReceive('getBuildPath')->once()->andReturn('bar/baz-123.css');
    $assets[1]->shouldReceive('getRelativePath')->once()->andReturn('bar/qux.css');
    $assets[1]->shouldReceive('getBuildPath')->once()->andReturn('bar/qux-321.css');

    $this->manifest->shouldReceive('make')->once()->with('foo')->andReturn($entry = m::mock('Basset\Manifest\Entry'));
    $entry->shouldReceive('hasDevelopmentAsset')->once()->with($assets[0])->andReturn(TRUE);
    $entry->shouldReceive('getDevelopmentAsset')->once()->with($assets[0])->andReturn('bar/baz-123.css');
    $entry->shouldReceive('hasDevelopmentAsset')->once()->with($assets[1])->andReturn(TRUE);
    $entry->shouldReceive('getDevelopmentAsset')->once()->with($assets[1])->andReturn('bar/qux-321.css');

    $entry->shouldReceive('hasDevelopmentAssets')->once()->with('stylesheets')->andReturn(TRUE);
    $entry->shouldReceive('getDevelopmentAssets')->once()->with('stylesheets')->andReturn(array(
      'bar/baz.css' => 'bar/baz-123.css',
      'bar/qux.css' => 'bar/qux-321.css'
    ));

    $this->builder->buildAsDevelopment($this->collection, 'stylesheets');
  }

  public function testBuildingDevelopmentCollectionWithNoCurrentManifestEntry()
  {
    $this->collection->shouldReceive('getAssetsWithoutRaw')->once()->with('stylesheets')
      ->andReturn(new Illuminate\Support\Collection(array(
      $assets[] = m::mock('Basset\Asset'),
      $assets[] = m::mock('Basset\Asset')
    )));
    $this->collection->shouldReceive('getIdentifier')->once()->andReturn('foo');

    $this->manifest->shouldReceive('make')->once()->with('foo')->andReturn($entry = m::mock('Basset\Manifest\Entry'));
    $entry->shouldReceive('hasDevelopmentAssets')->once()->with('stylesheets')->andReturn(FALSE);
    $entry->shouldReceive('resetDevelopmentAssets')->once()->with('stylesheets');

    $assets[0]->shouldReceive('getBuildPath')->once()->andReturn('bar/baz-123.css');
    $assets[0]->shouldReceive('build')->once()->andReturn('body { }');
    $assets[1]->shouldReceive('getBuildPath')->once()->andReturn('bar/qux-321.css');
    $assets[1]->shouldReceive('build')->once()->andReturn('html { }');

    $entry->shouldReceive('addDevelopmentAsset')->once()->with($assets[0]);
    $entry->shouldReceive('addDevelopmentAsset')->once()->with($assets[1]);

    $this->files->shouldReceive('exists')->once()->with('foo/foo/bar')->andReturn(FALSE);
    $this->files->shouldReceive('exists')->once()->with('foo/foo/bar')->andReturn(TRUE);
    $this->files->shouldReceive('makeDirectory')->once()->with('foo/foo/bar', 0777, TRUE);

    $this->files->shouldReceive('put')->once()->with('foo/foo/bar/baz-123.css', 'body { }');
    $this->files->shouldReceive('put')->once()->with('foo/foo/bar/qux-321.css', 'html { }');

    $this->builder->buildAsDevelopment($this->collection, 'stylesheets');
  }

  public function testBuildingDevelopmentCollectionWithNoChangesButWithForcing()
  {
    $this->collection->shouldReceive('getAssetsWithoutRaw')->once()->with('stylesheets')
      ->andReturn(new Illuminate\Support\Collection(array(
      $assets[] = m::mock('Basset\Asset'),
      $assets[] = m::mock('Basset\Asset')
    )));
    $this->collection->shouldReceive('getIdentifier')->once()->andReturn('foo');

    $assets[0]->shouldReceive('getRelativePath')->once()->andReturn('bar/baz.css');
    $assets[0]->shouldReceive('getBuildPath')->once()->andReturn('bar/baz-123.css');
    $assets[0]->shouldReceive('build')->once()->andReturn('body { }');
    $assets[1]->shouldReceive('getRelativePath')->once()->andReturn('bar/qux.css');
    $assets[1]->shouldReceive('getBuildPath')->once()->andReturn('bar/qux-321.css');
    $assets[1]->shouldReceive('build')->once()->andReturn('html { }');

    $this->manifest->shouldReceive('make')->once()->with('foo')->andReturn($entry = m::mock('Basset\Manifest\Entry'));
    $entry->shouldReceive('hasDevelopmentAssets')->once()->with('stylesheets')->andReturn(TRUE);
    $entry->shouldReceive('resetDevelopmentAssets')->once()->with('stylesheets');
    $entry->shouldReceive('getDevelopmentAssets')->once()->with('stylesheets')->andReturn(array(
      'bar/baz.css' => 'bar/baz-123.css',
      'bar/qux.css' => 'bar/qux-321.css'
    ));

    $entry->shouldReceive('addDevelopmentAsset')->once()->with($assets[0]);
    $entry->shouldReceive('addDevelopmentAsset')->once()->with($assets[1]);

    $this->files->shouldReceive('exists')->once()->with('foo/foo/bar')->andReturn(FALSE);
    $this->files->shouldReceive('exists')->once()->with('foo/foo/bar')->andReturn(TRUE);
    $this->files->shouldReceive('makeDirectory')->once()->with('foo/foo/bar', 0777, TRUE);

    $this->files->shouldReceive('put')->once()->with('foo/foo/bar/baz-123.css', 'body { }');
    $this->files->shouldReceive('put')->once()->with('foo/foo/bar/qux-321.css', 'html { }');

    $this->builder->setForce(TRUE);
    $this->builder->buildAsDevelopment($this->collection, 'stylesheets');
  }

  public function testBuildingDevelopmentCollectionWithGzip()
  {
    $this->collection->shouldReceive('getAssetsWithoutRaw')->once()->with('stylesheets')
      ->andReturn(new Illuminate\Support\Collection(array(
      $asset = m::mock('Basset\Asset')
    )));
    $this->collection->shouldReceive('getIdentifier')->once()->andReturn('foo');

    $asset->shouldReceive('getRelativePath')->once()->andReturn('bar/baz.css');
    $asset->shouldReceive('getBuildPath')->once()->andReturn('bar/baz-123.css');
    $asset->shouldReceive('build')->once()->andReturn('body { }');

    $this->manifest->shouldReceive('make')->once()->with('foo')->andReturn($entry = m::mock('Basset\Manifest\Entry'));
    $entry->shouldReceive('hasDevelopmentAssets')->once()->with('stylesheets')->andReturn(TRUE);
    $entry->shouldReceive('getDevelopmentAssets')->once()->with('stylesheets')
      ->andReturn(array('bar/baz.css' => 'bar/baz-123.css'));

    $entry->shouldReceive('hasDevelopmentAsset')->once()->with($asset)->andReturn(FALSE);
    $entry->shouldReceive('addDevelopmentAsset')->once()->with($asset);

    $this->files->shouldReceive('exists')->once()->with('foo/foo/bar')->andReturn(TRUE);

    $this->files->shouldReceive('put')->once()->with('foo/foo/bar/baz-123.css', gzencode('body { }', 9));

    $this->builder->setGzip(TRUE);
    $this->builder->buildAsDevelopment($this->collection, 'stylesheets');
  }


}