<?php
namespace Fetcher;

class Parallel
{
  protected $mh;

  protected $clients = array();

  protected $queue = array();

  /**
   * Constructor, create curl multi handle
   *
   */
  public function __construct()
  {
    $this->mh = curl_multi_init();
  }


  /**
   * Get handle
   *
   * @return resource
   */
  public function getHandle()
  {
    return $this->mh;
  }


  /**
   * Create and attach client
   *
   * @param string class
   */
  public function createClient($class = 'Fetcher\\Client')
  {
    $obj = new $class;
    $this->attach($obj);
    return $obj;
  }


  /**
   * Attach one or more clients
   *
   * @param array|Fetcher\Client
   * @return Fetcher\Parallel
   */
  public function attach($clients)
  {
    if (!is_array($clients)) {
      $clients = array($clients);
    }

    foreach ($clients as $client) {
      if (!$client instanceof Client) {
        throw new \Exception("Clients must be instances of Fetcher\\Client");
      }

      if (!in_array($client, $this->clients)) {
        $this->clients[] = $client;
      }
    }

    return $this;
  }


  /**
   * Queue client fetch
   *
   * @param Fetcher\Client
   */
  protected function queue(Client $client)
  {
    $ch   = $client->getHandle();
    $key  = intval($ch);
    $item = $client->shift();

    if (isset($this->queue[$key])) {
      curl_multi_remove_handle($this->mh, $ch);
      unset($this->queue[$key]);
    }

    if ($item) {
      $fn = isset($item[1]) ? $item[1] : null;
      $this->queue[$key] = array($client, $fn);
      curl_multi_add_handle($this->mh, $ch);
    }
  }


  /**
   * Execute clients in parallell
   *
   * @param array|Fetcher\Client
   */
  public function run($clients = null)
  {
    if ($clients !== null) {
      $this->attach($clients);
    }

    foreach ($this->clients as $client) {
      $this->queue($client);
    }

    // abort if empty queue
    if (empty($this->queue)) {
      return;
    }

    // run queue
    while ($this->curl_multi_exec()) {
      curl_multi_select($this->mh);
      $this->read();
    }

    // read any remaining responses
    $this->read();

    // requeue as needed
    $this->run();
  }


  /**
   * Read responses
   *
   */
  protected function read()
  {
    while ($info = curl_multi_info_read($this->mh)) {
      $key = intval($info['handle']);
      $raw = curl_multi_getcontent($info['handle']);
      list($client, $fn) = $this->queue[$key];

      $response = new Response($client, $raw);
      if ($fn) {
        call_user_func($fn, $response, $client, $this);
      }

      // requeue
      $this->queue($client);
    }
  }


  /**
   * Perform all curl_multi_exec
   *
   */
  protected function curl_multi_exec()
  {
    $active = null;
    do {
      $mrc = curl_multi_exec($this->mh, $active);
    } while($mrc == CURLM_CALL_MULTI_PERFORM);

    return $active;
  }
}
