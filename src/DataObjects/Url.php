<?php
/**
 * @package   multiplex_curl
 * @package   RK\MultiplexCurl\DataObjects
 * @copyright © 2017 by Wood Street, Inc.
 */

namespace RK\MultiplexCurl\DataObjects;

class Url
{

  protected string $scheme;
  protected string $host;
  protected ?int $port;
  protected string $user;
  protected string $pass;
  protected string $path;
  protected array $query;
  protected string $fragment;

  public function __construct()
  {
    $this->scheme   = '';
    $this->host     = '';
    $this->port     = null;
    $this->user     = '';
    $this->pass     = '';
    $this->path     = '/';
    $this->query    = [];
    $this->fragment = '';
  }

  public static function fromString(string $string): Url
  {
    $result = new self();

    if ($string !== '') {
      $parts = parse_url($string);

      foreach ($parts as $key => $value) {
        $setter = 'set' . ucfirst($key);
        $result->$setter($value);
      }
    }

    return $result;
  }

  public function getScheme(): string
  {
    return $this->scheme;
  }

  public function setScheme(string $scheme): void
  {
    $this->scheme = $scheme;
  }

  public function getHost(): string
  {
    return $this->host;
  }

  /**
   * @param string $host
   */
  public function setHost(string $host): void
  {
    $this->host = $host;
  }

  public function getPort(): ?int
  {
    return $this->port;
  }

  public function setPort(?int $port): void
  {
    $this->port = $port;
  }

  public function getUser(): string
  {
    return $this->user;
  }

  public function setUser(string $user): void
  {
    $this->user = $user;
  }

  public function getPass(): string
  {
    return $this->pass;
  }

  public function setPass(string $pass): void
  {
    $this->pass = $pass;
  }

  public function getPath(): string
  {
    return $this->path;
  }

  public function setPath(string $path): void
  {
    $this->path = $path ?: '/';
  }

  public function getQuery(): array
  {
    return $this->query;
  }

  /**
   * @param array|string $query
   */
  public function setQuery($query): void
  {
    if (\is_string($query)) {
      \parse_str($query, $this->query);
    } elseif (\is_array($query)) {
      $this->query = $query;
    }
  }

  public function getFragment(): string
  {
    return $this->fragment;
  }

  public function setFragment(string $fragment): void
  {
    $this->fragment = $fragment;
  }

  public function __toString(): string
  {
    $url = "{$this->getSchemePart()}//{$this->getHostnamePart()}";
    $url .= $this->path;

    if (\count($this->query) > 0) {
      $url .= '?' . \http_build_query($this->query);
    }

    if ($this->fragment !== '') {
      $url .= '#' . $this->fragment;
    }

    return $url;
  }

  protected function getSchemePart(): string
  {
    return $this->scheme
      ? $this->scheme . ':'
      : '';
  }

  protected function getHostnamePart(): string
  {
    $part = '';

    if ($this->user !== '') {
      $part .= $this->user;

      if ($this->pass !== '') {
        $part .= ':' . $this->pass;
      }

      $part .= '@';
    }

    $part .= $this->host;

    if ($this->port !== null) {
      $part .= ':' . $this->port;
    }

    return $part;
  }

}