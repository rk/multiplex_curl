<?php
/**
 * @package   multiplex_curl
 * @package   RK\MultiplexCurl\Tests
 * @copyright © 2017 by Wood Street, Inc.
 */

namespace RK\MultiplexCurl\Tests;

use RK\MultiplexCurl\Client;
use RK\MultiplexCurl\DataObjects\Url;
use RK\MultiplexCurl\Request;
use RK\MultiplexCurl\Response;

class ClientTest extends \PHPUnit_Framework_TestCase
{

  /**
   * @medium
   */
  public function testFetchingMultipleSearchPages()
  {
    $url    = Url::fromString('https://www.google.com/search');
    $client = new Client();

    for ($offset = 0; $offset < 100; $offset += 10) {
      $newUrl = clone $url;
      $newUrl->setQuery([
        'q'      => 'php multiplex curl',
        'offset' => $offset,
      ]);

      $request = new Request($newUrl);
      $client->addRequest($request);
    }

    /** @var Response[] $responses */
    $responses = $client->execute();

    $this->assertCount(10, $responses);

    foreach ($responses as $response) {
      $this->assertTrue($response->isSuccessful());
      $this->assertNotEmpty($response->getBody());
    }
  }

}
