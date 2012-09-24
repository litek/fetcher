cURL wrapper for fetching URLs in parallel

```php
$google = new Fetcher\Client;
$google->queue('http://google.com/', function($response) {
  // handle response
});

$bing = new Fetcher\Client;
$bing->queue('http://bing.com/', function($response, $bing, $master) {
  // you can also queue a new url for fetching when reacting on a response
  $bing->queue('http://url', function($response) {
    // handle response
  });

  // or even attach another client for parallel retrieval
  $yahoo = $master->createClient();
  $yahoo->queue('http://yahoo.com/', function($response) {
    // handle this response
  });
});

// run in parallel
$master = new Fetcher\Parallel;
$master->run([$google, $bing]);

// will be done after the slowest request chain, instead of the sum of requests
echo "Done.";
```
Multiple requests queued on the same client will be run sequentially.

Can also be used as a cURL wrapper for a single request.

```php
$example = new Fetcher\Client;
$example->queue('http://example.org/', function($response) {
  // handle response
})->run();
```
