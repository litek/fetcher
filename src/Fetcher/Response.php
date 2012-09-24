<?php
namespace Fetcher;

class Response
{
  protected $code;

  protected $headers;

  protected $body;

  /**
   * Construct new response from client result
   *
   * @param Fetcher\Client
   * @param string $raw
   */
  public function __construct(Client $client, $raw)
  {
    // retrieve only the last header
    $count  = curl_getinfo($client->getHandle(), CURLINFO_REDIRECT_COUNT);
    $buffer = preg_split('/(\r?\n){2}/', $raw, $count+2);
    list($header, $this->body) = array_slice($buffer, -2);

    // parse header
    $headers = preg_split('/\r?\n/', $header);
    $this->code = preg_replace('#HTTP/1.\d (\d{3}).*#', '$1', $headers[0]);
    for ($i=1, $c=count($headers); $i<$c; ++$i) {
      list($key, $val) = explode(': ', $headers[$i], 2);
      $this->headers[strtolower($key)] = $val;
    }
  }


  /**
   * Get status code
   *
   * @return int
   */
  public function statusCode()
  {
    return $this->code;
  }


  /**
   * Get header value, or all headers
   *
   * @param string $key
   * @param string $default
   * @return string
   */
  public function header($key = null, $default = null)
  {
    if ($key === null) {
      return $this->headers;
    }

    $key = strtolower($key);
    return isset($this->headers[$key]) ? $this->headers[$key] : $default;
  }


  /**
   * Get request body
   *
   * @return string
   */
  public function body()
  {
    return $this->body;
  }


  /**
   * Get JSON decoded response if applicable
   *
   * @return object
   */
  public function json()
  {
    $isJSON = strpos('application/json', $this->header('content-type')) !== false;
    return $isJSON ? json_decode($this->body) : false;
  }
}
