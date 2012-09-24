<?php
namespace Fetcher\Test;
use Fetcher\Client;
use Fetcher\Response;

class ClientTest extends \PHPUnit_Framework_TestCase
{
  public function testGetHandle()
  {
    $client = new Client;
    $this->assertInternalType('resource', $client->getHandle());
  }

  public function testQueue()
  {
    $client = new Client;
    $client->queue('http://localhost/', function() {});
    $client->queue('http://localhost/', 'func');
    $client->queue('http://localhost/obj/', array('obj', 'method'));

    $refl  = new \ReflectionObject($client);
    $queue = $refl->getProperty('queue');
    $queue->setAccessible(true);
    $array = $queue->getValue($client);

    $this->assertInternalType('array', $array);
    $this->assertEquals(3, count($array));
    $this->assertEquals(array('http://localhost/', 'func'), $array[1]);
  }

  public function testRun()
  {
    $client = new Client;
    $called = false;
    $client->queue('http://localhost/', function($response, $client) use(&$called) {
      $this->assertInstanceOf('Fetcher\\Response', $response);
      $this->assertInstanceOf('Fetcher\\Client', $client);
      $called = true;
    });

    $client->run();
    $this->assertTrue($called);
  }
}
