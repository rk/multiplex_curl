<?php

namespace RK\MultiplexCurl\Tests;

use PHPUnit_Framework_TestCase;
use RK\MultiplexCurl\Response;

/**
 * @package   multiplex_curl
 * @copyright © 2017 by Wood Street, Inc.
 */
class ResponseTest extends PHPUnit_Framework_TestCase
{

  public function testResponseCreationFromCurl()
  {
    // Make a request to Google; safest assumption of a 200 OK response
    $ch = curl_init('https://www.google.com/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);

    $response = Response::fromCurl($ch);

    curl_close($ch);

    $this->assertTrue($response->isSuccessful());
    $this->assertFalse($response->isError());
    $this->assertFalse($response->isRedirect());

    $this->assertEquals(200, $response->getHttpCode());
    $this->assertEquals('text/html', $response->getContentType());
    $this->assertEquals('https://www.google.com/', $response->getUrl());
    $this->assertNotEmpty($response->getCharset());
    $this->assertNotEmpty($response->getTime());
    $this->assertNotEmpty($response->getBody());
  }

}
