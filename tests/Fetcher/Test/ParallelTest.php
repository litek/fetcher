<?php
namespace Fetcher\Test;
use Fetcher\Parallel;
use Fetcher\Client;
use Fetcher\Response;

class ParallelTest extends \PHPUnit_Framework_TestCase
{
  public function testGetHandle()
  {
    $master = new Parallel;
    $this->assertInternalType('resource', $master->getHandle());
  }

  public function testCreateHandle()
  {
    $master = new Parallel;
    $this->assertInstanceOf('Fetcher\\Client', $master->createClient());
  }

  public function testAttach()
  {
    $master = new Parallel;
    $master->attach(new Client);
    $master->attach([new Client, new Client]);

    $refl = new \ReflectionObject($master);
    $prop = $refl->getProperty('clients');
    $prop->setAccessible(true);
    $clients = $prop->getValue($master);

    $this->assertEquals(3, count($clients));
  }

  public function testRun()
  {
    $called = 0;

    $one = new Client;
    $one->queue('http://localhost/', function($response, $client, $master) use($one, &$called) {
      $this->assertEquals($one, $client);
      $this->assertInstanceOf('Fetcher\\Response', $response);
      $called++;
    });

    $two = new Client;
    $two->queue('http://localhost/', function($response, $two, $master) use(&$called) {
      $called++;

      $two->queue('http://localhost/', function() use(&$called) {
        $called++;
      });

      $master->createClient()->queue('http://localhost/', function() use(&$called) {
        $called++;
      });
    });

    $master = new Parallel;
    $master->run(array($one, $two));

    $this->assertEquals(4, $called);
  }
}
