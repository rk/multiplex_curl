<?php
/**
 * @package   multiplex_curl
 * @package   RK\MultiplexCurl\Tests
 * @copyright © 2017 by Wood Street, Inc.
 */

namespace RK\MultiplexCurl\Tests;

use RK\MultiplexCurl\Request;
use RK\MultiplexCurl\Response;

class RequestTest extends \PHPUnit_Framework_TestCase
{

  public function testHeaderAggregation()
  {
    $step1 = [
      'X-Custom-1' => 'Foo Bar',
      'X-Custom-2' => time(),
    ];

    $step2 = [
      'X-Custom-1' => 'Baz Qux',
      'X-Custom-3' => 123,
    ];

    $request = new Request('https://www.woodst.com/');
    $request->expectType('text/html');

    $request->addHeaders($step1);
    $this->assertEquals([
      'X-Custom-1' => $step1['X-Custom-1'],
      'X-Custom-2' => $step1['X-Custom-2'],
      'Accept'     => 'text/html',
    ], $request->getHeaders());

    $request->addHeaders($step2);
    $this->assertEquals([
      'X-Custom-1' => $step2['X-Custom-1'],
      'X-Custom-2' => $step1['X-Custom-2'],
      'X-Custom-3' => $step2['X-Custom-3'],
      'Accept'     => 'text/html',
    ], $request->getHeaders());
  }

  public function testCompletionCallback()
  {
    $request  = new Request('https://www.woodst.com/');
    $response = new Response();
    $result   = false;

    $request->setCallback(function ($response) use (&$result) {
      $result = $response instanceof Response;
    });

    $this->assertFalse($result);
    $request->complete($response);
    $this->assertTrue($result);
  }

  public function testPostAsJson()
  {
    $data = [
      'foo' => 1,
      'bar' => 'baz',
      'qux' => true,
    ];

    $request = (new Request('https://www.woodst.com/'))
      ->postAsJSON($data);

    $this->assertEquals('POST', $request->getMethod());

    $data = json_encode($data);

    $this->assertEquals($data, $request->getFields());

    $this->assertEquals([
      'Content-Type'   => 'application/json; charset="UTF-8"',
      'Content-Length' => mb_strlen($data, 'ascii'),
    ], $request->getHeaders());
  }

  public function testPostAsXml()
  {
    $data = '<foo><bar baz="qux">wat?</bar></foo>';

    $request = (new Request('https://www.woodst.com/'))
      ->postAsXML($data);

    $this->assertEquals('POST', $request->getMethod());
    $this->assertEquals($data, $request->getFields());

    $this->assertEquals([
      'Content-Type'   => 'text/xml; charset="UTF-8"',
      'Content-Length' => mb_strlen($data, 'ascii'),
    ], $request->getHeaders());
  }

}
