<?php
/**
 * @package   multiplex_curl
 * @package   RK\MultiplexCurl
 * @copyright © 2016 by Wood Street, Inc.
 */

namespace RK\MultiplexCurl;

class Response
{

  public const CONTENT_TYPE_REGEX = '/(?<type>\w+\/\w+)(?:; charset=(?<char>[\w-]+))/i';

  /** @var string */
  protected $url;
  /** @var int */
  protected $httpCode;
  /** @var string */
  protected $contentType;
  /** @var string|null */
  protected $charset;
  /** @var string|null */
  protected $body;
  /** @var float */
  protected $time;
  /** @var float */
  protected $start;

  public static function fromCurl($handle): Response
  {
    $result = new self();

    // Get the URL from the handle.
    $result->url = curl_getinfo($handle, CURLINFO_EFFECTIVE_URL);

    $result->httpCode = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
    // Get the response body, may be relevant even in failure.
    $result->body = curl_multi_getcontent($handle);
    // Adjust the duration for the connection time: opening this many
    // HTTP connections to the server can cause delays due to QoS, etc.
    $result->start = curl_getinfo($handle, CURLINFO_CONNECT_TIME);
    $result->time  = curl_getinfo($handle, CURLINFO_TOTAL_TIME) - $result->start;

    if (preg_match(self::CONTENT_TYPE_REGEX, curl_getinfo($handle, CURLINFO_CONTENT_TYPE), $matches)) {
      $result->contentType = strtolower($matches['type']);
      $result->charset     = strtolower($matches['char']) ?? null;
    }

    return $result;
  }

  /**
   * @return string
   */
  public function getUrl(): string
  {
    return $this->url;
  }

  /**
   * @return int
   */
  public function getHttpCode(): int
  {
    return $this->httpCode;
  }

  /**
   * @return string|null
   */
  public function getContentType(): ?string
  {
    return $this->contentType;
  }

  /**
   * @return string|null
   */
  public function getCharset(): ?string
  {
    return $this->charset;
  }

  /**
   * @return string
   */
  public function getBody(): string
  {
    return $this->body;
  }

  /**
   * @return float
   */
  public function getTime(): float
  {
    return $this->time;
  }

  /**
   * @return float
   */
  public function getStart(): float
  {
    return $this->start;
  }

  /**
   * @return bool
   */
  public function isSuccessful(): bool
  {
    return $this->httpCode >= 200 && $this->httpCode < 300;
  }

  /**
   * @return bool
   */
  public function isRedirect(): bool
  {
    return \in_array($this->httpCode, [
      201, // Created
      301, // Moved Permanently (GET)
      302, // Found (GET)
      303, // See Other
      307, // Temporary Redirect (POST)
      308, // Permanent Redirect (POST)
    ], true);
  }

  /**
   * @return bool
   */
  public function isError(): bool
  {
    return $this->httpCode >= 400;
  }

}