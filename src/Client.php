<?php
/**
 * @package   multiplex_curl
 * @package   RK\MultiplexCurl
 * @copyright © 2016 by Wood Street, Inc.
 */

namespace RK\MultiplexCurl;

class Client
{

  /** @var int How many requests are allowed to process at once. */
  protected $multiplexLimit = 5;

  /** @var int How many times a request is allowed to retry after timing out before it fails. */
  protected $maxAttempts = 1;

  protected $options = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => 1,
    CURLOPT_MAXREDIRS      => 5,
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_TIMEOUT        => 30,
  ];

  protected $headers = [];

  /** @var ThrottleInterface */
  protected $throttle;

  /** @var \SplStack */
  protected $requests = [];

  /** @var bool Is the execution loop running? */
  protected $running = false;
  protected $onComplete;

  /**
   * Client constructor.
   *
   * @param ThrottleInterface|null $throttle
   */
  public function __construct(ThrottleInterface $throttle = null)
  {
    $this->requests = new \SplStack();
    $this->throttle = $throttle ?? new OpenThrottle();
  }

  /**
   * An array of curl options to overwrite.
   *
   * @param array $options
   */
  public function addOptions(array $options): void
  {
    $this->options = array_replace($this->options, $options);
  }

  /**
   * Headers in $key => $value format, which will be reformatted to
   * "$key: $value" format.
   *
   * @param array $headers
   */
  public function addHeaders(array $headers): void
  {
    $this->headers = array_replace($this->headers, $headers);
  }

  public function addRequest(Request $request): void
  {
    $this->requests->push($request);
  }

  /**
   * @return Response[]
   */
  public function execute(): array
  {
    if ($this->running === true) {
      return [];
    }

    $this->running = true;

    $master  = curl_multi_init();
    $active  = new \SplObjectStorage();
    $output  = [];
    $running = false;

    $retryCounter = new \SplObjectStorage();

    do {
      $this->throttle->tick();

      if ($this->throttle->canRequest()) {
        // At the start of the loop enqueue as many available requests as will fit into our queue
        while (!$this->requests->isEmpty() && \count($active) < $this->multiplexLimit) {
          /** @var Request $request */
          $request = $this->requests->pop();
          $handle  = curl_init();

          curl_setopt_array($handle, $this->options);
          $request->configureCurl($handle, $this->headers);

          $active[$handle] = $request;

          curl_multi_add_handle($master, $handle);
          $retryCounter->attach($request, 0);
        }

        unset($request);
      } elseif ($delay = $this->throttle->estimate()) {
        // If the throttle isn't happy and we have no active connections sleep;
        // otherwise, continue on into the processing branch(es) below this block.
        // FIXME: add support for PSR logging!
        // importer_logger('Waiting due to request throttling...');
        usleep($delay * 1000);
        continue;
      }

      do {
        $status = curl_multi_exec($master, $running);
        // curl_multi_select() waits for progress/completion
        curl_multi_select($master);
      } while ($status === CURLM_CALL_MULTI_PERFORM);

      if ($status !== CURLM_OK) {
        $this->running = false;

        // At this point, the whole multi-curl operation hasn't failed.
        curl_multi_close($master);
        throw new \RuntimeException('cURL Error: ' . curl_multi_strerror($status), $status);
      }

      while ($done = curl_multi_info_read($master)) {
        $ch = $done['handle'];
        /** @var Request $request */
        $request = $active[$ch];

        $this->throttle->recordCall(microtime(true) - curl_getinfo($ch, CURLINFO_TOTAL_TIME));

        if ($retryCounter[$request] < $this->maxAttempts && (
            $done['result'] === CURLE_OPERATION_TIMEOUTED ||
            curl_getinfo($ch, CURLINFO_HTTP_CODE) === 429
          )) {
          $retryCounter[$request] += 1;
          curl_multi_remove_handle($master, $ch);
          curl_multi_add_handle($master, $ch);
          continue;
        }

        // Remove the completed request from the active jobs list
        unset($active[$ch]);
        $retryCounter->detach($request);

        $result   = Response::fromCurl($ch);
        $output[] = $result;

        if (\is_callable($this->onComplete)) {
          \call_user_func($this->onComplete, $request, $result);
        }

        $request->complete($result);

        curl_multi_remove_handle($master, $ch);
        curl_close($ch);

        unset($request, $result, $ch);
      }
    } while ($running);

    curl_multi_close($master);
    $this->running = false;

    return $output;
  }

  /**
   * @return ThrottleInterface
   */
  public function getThrottle(): ThrottleInterface
  {
    return $this->throttle;
  }

  /**
   * @param ThrottleInterface $throttle
   */
  public function setThrottle(ThrottleInterface $throttle): void
  {
    $this->throttle = $throttle;
  }

  public function getPendingRequestCount(): int
  {
    return \count($this->requests);
  }

  /**
   * @return int How many connections can be requested in parallel.
   */
  public function getMultiplexLimit(): int
  {
    return $this->multiplexLimit;
  }

  /**
   * @param int $multiplexLimit How many connections can be requested in parallel.
   */
  public function setMultiplexLimit(int $multiplexLimit): void
  {
    $this->multiplexLimit = $multiplexLimit;
  }

  /**
   * @return int How many times the request can be requested, if there was a timeout.
   */
  public function getMaxAttempts(): int
  {
    return $this->maxAttempts;
  }

  /**
   * @param int $maxAttempts How many times the request can be requested, if there was a timeout.
   */
  public function setMaxAttempts(int $maxAttempts): void
  {
    $this->maxAttempts = $maxAttempts;
  }

  public function setOnComplete(\Closure $cb): void
  {
    $this->onComplete = $cb->bindTo($this);
  }

  /**
   * Is the request handler currently running?
   *
   * @return bool
   */
  public function isRunning(): bool
  {
    return $this->running;
  }

  public function hasRequests(): bool
  {
    return !$this->requests->isEmpty();
  }

}