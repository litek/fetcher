<?php
// dirty hack to mock CURLINFO_REDIRECT_COUNT
namespace Fetcher
{
  $curl_info = null;

  function curl_getinfo() {
    $val = $GLOBALS['curl_info'];
    $GLOBALS['curl_info'] = null;
    return $val;
  }

  function curl_setinfo($val) {
    $GLOBALS['curl_info'] = $val;
  }
}

namespace Fetcher\Test
{
  use Fetcher\Client;
  use Fetcher\Response;

  class ResponseTest extends \PHPUnit_Framework_TestCase
  {
    public function testRequest($prepend = '')
    {
      $body   = 'bodyrock';
      $header = $prepend
              . "HTTP/1.1 200 OK\r\n"
              . "Content-Type: text/html\n"
              . "X-Powered-By: Fetcher\r\n"
              . "Content-Lenth: ".strlen($body)."\r\n\r\n";

      $client = new Client;
      $res    = new Response($client, $header.$body);

      $this->assertEquals(200, $res->statusCode());
      $this->assertEquals("text/html", $res->header('Content-type'));
      $this->assertInternalType('array', $res->header());
      $this->assertEquals('bodyrock', $res->body());
      $this->assertFalse($res->json());
    }

    public function testRedirect()
    {
      $header = "HTTP/1.1 302 Found\n"
              . "Location: http://foo/\n\n";

      \Fetcher\curl_setinfo(2);
      $this->testRequest($header);
    }

    public function testJson()
    {
      $body   = '{"title": "JSON!"}';
      $header = "HTTP/1.1 200 OK\n"
              . "Content-Type: application/json\n\n";

      $client = new Client;
      $res    = new Response($client, $header.$body);

      $this->assertEquals(200, $res->statusCode());
      $this->assertEquals('application/json', $res->header('content-type'));
      $this->assertInternalType('object', $res->json());
      $this->assertEquals('JSON!', $res->json()->title);
    }
  }
}
