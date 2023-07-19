<?php
/**
 * Copyright © 2018 by Wood Street, Inc. All Rights reserved.
 */

namespace RK\MultiplexCurl;

interface ThrottleInterface
{

  /**
   * Returns true if requests can be made.
   *
   * @return bool
   */
  public function canRequest(): bool;

  /**
   * Records a method call.
   *
   * @param float|null $when Timestamp of the call (in seconds); defaults to current time
   */
  public function recordCall(float $when = null): void;

  /**
   * Returns an estimate of how long until the next call.
   *
   * @return int|null
   */
  public function estimate(): ?int;

  /**
   * Updates any internal logic when calls complete/fail.
   */
  public function tick(): void;

  /**
   * Resets the throttle to a blank slate, in case of a recoverable critical error.
   */
  public function reset(): void;
}