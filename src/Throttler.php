<?php
/**
 * Copyright © 2018 by Wood Street, Inc. All Rights reserved.
 */

namespace RK\MultiplexCurl;

class Throttler implements ThrottleInterface
{

  protected $ratePerSecond = 100;
  protected $previousCalls = [];
  protected $validity      = true;

  /**
   * @return int
   */
  public function getRatePerSecond(): int
  {
    return $this->ratePerSecond;
  }

  /**
   * @param int $ratePerSecond
   */
  public function setRatePerSecond(int $ratePerSecond): void
  {
    $this->ratePerSecond = $ratePerSecond;
  }

  /**
   * {@inheritdoc}
   */
  public function canRequest(): bool
  {
    return $this->validity;
  }

  /**
   * {@inheritdoc}
   */
  public function recordCall(float $when = null): void
  {
    $this->previousCalls[] = $when ?? microtime(true);
  }

  /**
   * {@inheritdoc}
   */
  public function estimate(): ?int
  {
    if (!empty($this->previousCalls)) {
      return abs(microtime(true) - $this->previousCalls[0]);
    }

    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function tick(): void
  {
    $this->purgeOlderCalls();

    $this->validity = \count($this->previousCalls) < $this->ratePerSecond;
  }

  /**
   * {@inheritdoc}
   */
  public function reset(): void
  {
    $this->previousCalls = [];
    $this->validity      = true;
  }

  protected function purgeOlderCalls(): void
  {
    if (empty($this->previousCalls)) {
      return;
    }

    $cutoff = microtime(true) - 1;

    $this->previousCalls = array_values(array_filter($this->previousCalls, function (float $when) use ($cutoff) {
      return $when >= $cutoff;
    }));
  }

}