<?php

use Mockery as m;

class BuildCommandTest extends \TestCase {
  public function tearDown()
  {
    m::close();
  }

  public function setUp()
  {
    parent::setup();
  }

  public function testArtisanBuildCommand()
  {

    $environment       = m::mock('\Basset\Environment');
    $builder           = m::mock('Basset\Builder\Builder');
    $filesystemCleaner = m::mock('Basset\Builder\FilesystemCleaner');

    $buildCommand = new Basset\Console\BuildCommand($environment, $builder, $filesystemCleaner);

    \Config::shouldReceive('get')->with('basset.manifest')->once()->andReturn(app_path() . '/meta');
    \File::shouldReceive('exists')->once()->andReturn('true');
    \File::shouldReceive('delete')->once();

    $environment->shouldReceive('all')->once()->andReturn(array());

    $buildCommand->run(new Symfony\Component\Console\Input\ArrayInput(array('--production' => 'true')),
      new Symfony\Component\Console\Output\NullOutput());

  }
}