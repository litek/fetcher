<?php
namespace Fetcher;

class Client
{
  protected $ch;

  protected $queue = array();

  /**
   * Constructor, create cURL handle
   *
   */
  public function __construct()
  {
    $this->ch = curl_init();
    curl_setopt_array($this->ch, array(
      \CURLOPT_FOLLOWLOCATION => true,
      \CURLOPT_MAXREDIRS => 5,
      \CURLOPT_TIMEOUT => 5
    ));
  }


  /**
   * Get cURL handle
   *
   * @return resource
   */
  public function getHandle()
  {
    return $this->ch;
  }


  /**
   * Queue URL to sequentially fetch, with optional callback
   *
   * @param string $url
   * @param callback
   * @return Fetcher\Client
   */
  public function queue($url, $callback = null)
  {
    $this->queue[] = array($url, $callback);
    return $this;
  }


  /**
   * Shift from queue and prepare fetch options
   *
   * @return array
   */
  public function shift()
  {
    $next = array_shift($this->queue);
    if ($next) {
      curl_setopt_array($this->ch, array(
        \CURLOPT_URL => $next[0],
        \CURLOPT_RETURNTRANSFER => true,
        \CURLOPT_HEADER => true
      ));
    }

    return $next;
  }


  /**
   * Process queue
   *
   */
  public function run()
  {
    while (count($this->queue)) {
      list($url, $fn) = $this->shift();

      $raw = curl_exec($this->ch);
      $response = new Response($this, $raw);
      if ($fn) {
        call_user_func($fn, $response, $this);
      }
    }
  }
}
