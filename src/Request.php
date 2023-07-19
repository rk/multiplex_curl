<?php
/**
 * @package   multiplex_curl
 * @package   RK\MultiplexCurl
 * @copyright © 2016 by Wood Street, Inc.
 */

namespace RK\MultiplexCurl;

use CurlHandle;

class Request
{

  /** @var DataObjects\Url */
  protected $url;

  /** @var string */
  protected $method;

  /** @var array|string */
  protected $fields = [];

  /** @var array */
  protected $options = [];

  /** @var array */
  protected $headers = [];

  /** @var callable Completion callback */
  protected $callback;

  /**
   * Request constructor.
   *
   * @param string $url A URL
   * @param string $method One of: GET, POST, OPTION, HEAD, PUT, DELETE
   */
  public function __construct(string $url, string $method = 'GET')
  {
    $this->setUrl($url);
    $this->setMethod($method);
  }

  /**
   * Low-level method to batch-set this as a POST-AS, so you can assign both
   * data fields and the request method all at once.
   *
   * @param string $fields
   * @param string $content_type
   * @param string $method
   * @return Request
   */
  public function postAsType(string $fields, string $content_type, string $method = 'POST'): Request
  {
    return $this->setMethod($method)
      ->setFields($fields)
      ->addHeaders([
        'Content-Type'   => "{$content_type}; charset=UTF-8",
        'Content-Length' => mb_strlen($this->fields, 'ascii'),
      ]);
  }

  /**
   * Helper method to set to a POST request with its fields serialized in
   * JSON format.
   *
   * @param mixed $fields Data which will be serialized as JSON
   * @param string $method
   * @return Request
   */
  public function postAsJSON($fields, string $method = 'POST'): Request
  {
    return $this->postAsType(json_encode($fields), 'application/json', $method);
  }

  /**
   * Helper method to set as a POST request with its body pre-serialized in
   * XML format.
   *
   * @param string $fields
   * @param string $method
   * @return Request
   */
  public function postAsXML(string $fields, string $method = 'POST'): Request
  {
    return $this->postAsType($fields, 'text/xml', $method);
  }

  public function expectType(string $content_type): Request
  {
    return $this->addHeaders([
      'Accept' => $content_type,
    ]);
  }

  /**
   * @return DataObjects\Url
   */
  public function getUrl(): DataObjects\Url
  {
    return $this->url;
  }

  /**
   * @param string $url
   * @return Request
   */
  public function setUrl(string $url): Request
  {
    $this->url = $url instanceof DataObjects\Url ? $url : DataObjects\Url::fromString($url);

    return $this;
  }

  /**
   * @return string
   */
  public function getMethod(): string
  {
    return $this->method;
  }

  /**
   * @param string $method One of: GET, POST, OPTION, HEAD, PUT, DELETE
   * @return Request
   */
  public function setMethod(string $method): Request
  {
    $this->method = strtoupper($method);

    return $this;
  }

  /**
   * @return array|string
   */
  public function getFields()
  {
    return $this->fields;
  }

  /**
   * @param array|string $fields
   * @return Request
   */
  public function setFields($fields): Request
  {
    $this->fields = $fields;

    return $this;
  }

  /**
   * @return array
   */
  public function getOptions(): array
  {
    return $this->options;
  }

  /**
   * An array of curl options to overwrite.
   *
   * @param array $options
   * @return Request
   */
  public function addOptions(array $options): Request
  {
    $this->options = array_replace($this->options, $options);

    return $this;
  }

  /**
   * @return array
   */
  public function getHeaders(): array
  {
    return $this->headers;
  }

  /**
   * @param array $headers
   * @return $this
   */
  public function addHeaders(array $headers): self
  {
    $this->headers = array_replace($this->headers, $headers);

    return $this;
  }

  /**
   * Configures the curl connection handle.
   *
   * @param CurlHandle $handle
   * @param array $baseHeaders
   */
  public function configureCurl(CurlHandle $handle, array $baseHeaders = []): void
  {
    $options = $this->options;

    $options[CURLOPT_URL] = $this->url;

    if ($this->method === 'HEAD') {
      $options[CURLOPT_NOBODY] = true;
    }

    if ($this->method === 'POST' && $this->fields) {
      $options[CURLOPT_POSTFIELDS] = $this->fields;
    }

    if ($this->headers || $baseHeaders) {
      $headers = array_replace($baseHeaders, $this->headers);

      foreach ($headers as $key => $value) {
        $options[CURLOPT_HTTPHEADER][] = "{$key}: {$value}";
      }
    }

    curl_setopt_array($handle, $options);
  }

  /**
   * @param callable $callback Should accept a Response and a Request, in that order.
   */
  public function setCallback(callable $callback): void
  {
    $this->callback = $callback;
  }

  /**
   * Executes the callback method, if available.
   *
   * @param Response $response
   */
  public function complete(Response $response): void
  {
    if (\is_callable($this->callback)) {
      \call_user_func($this->callback, $response, $this);
    }
  }
}