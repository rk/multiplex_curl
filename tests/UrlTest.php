<?php
/**
 * @package   multiplex_curl
 * @package   RK\MultiplexCurl\Tests
 * @copyright © 2017 by Wood Street, Inc.
 */

namespace RK\MultiplexCurl\Tests;

use RK\MultiplexCurl\DataObjects\Url;

class UrlTest extends \PHPUnit_Framework_TestCase
{

  public function testParsing()
  {
    $actual = 'https://duckduckgo.com/?q=write+composer+package+with+phpunit+tests&ia=web';
    $url    = Url::fromString($actual);

    $this->assertEquals('https', $url->getScheme());
    $this->assertEquals('duckduckgo.com', $url->getHost());
    $this->assertEquals([
      'q'  => 'write composer package with phpunit tests',
      'ia' => 'web',
    ], $url->getQuery());

    // Ensure the URL is properly reconstructed
    $this->assertEquals($actual, (string)$url);
  }

  public function testHttpBasicAuth()
  {
    $url = new Url();
    $url->setScheme('https');
    $url->setHost('www.woodst.com');
    $url->setUser('username');
    $url->setPass('password');

    $this->assertEquals('https://username:password@www.woodst.com/', (string)$url);
  }

  public function testHttpWithPort()
  {
    $url = new Url();
    $url->setScheme('https');
    $url->setHost('www.woodst.com');
    $url->setPort(8080);

    $this->assertEquals('https://www.woodst.com:8080/', (string)$url);
  }

  public function testHttpQueryGeneration()
  {
    $url = new Url();
    $url->setScheme('https');
    $url->setHost('www.woodst.com');
    $url->setQuery(['s' => 'search engine optimization']);

    $this->assertEquals('https://www.woodst.com/?s=search+engine+optimization', (string)$url);
  }

}
