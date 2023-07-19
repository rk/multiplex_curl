<?php
/**
 * Copyright © 2018 by Wood Street, Inc. All Rights reserved.
 */

namespace RK\MultiplexCurl;

/**
 * An OpenThrottle always says the request can be made.
 *
 * @package RK\MultiplexCurl
 */
class OpenThrottle implements ThrottleInterface
{

  /**
   * {@inheritdoc}
   */
  public function canRequest(): bool
  {
    return true;
  }

  /**
   * {@inheritdoc}
   */
  public function recordCall(float $when = null): void
  {
  }

  /**
   * {@inheritdoc}
   */
  public function estimate(): ?int
  {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function tick(): void
  {
  }

  /**
   * {@inheritdoc}
   */
  public function reset(): void
  {
  }

}