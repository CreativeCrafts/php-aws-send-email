<?php

declare(strict_types=1);

namespace CreativeCrafts\EmailService\Services\RateLimiter;

use CreativeCrafts\EmailService\Interfaces\RateLimiterInterface;
use Redis;
use RuntimeException;

/**
 * RedisRateLimiter implements rate limiting functionality using Redis.
 */
class RedisRateLimiter implements RateLimiterInterface
{
    private Redis $redis;

    private int $maxAttempts;

    private int $decayMinutes;

    /**
     * Constructs a new RedisRateLimiter instance.
     *
     * @param Redis $redis The Redis instance to use for rate limiting.
     * @param int $maxAttempts The maximum number of attempts allowed within the decay period. Defaults to 5.
     * @param int $decayMinutes The number of minutes for the rate limit to decay. Defaults to 1.
     */
    public function __construct(Redis $redis, int $maxAttempts = 5, int $decayMinutes = 1)
    {
        $this->redis = $redis;
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
    }

    /**
     * Determines if the request is allowed based on the rate limit.
     *
     * @param string $key The base key for the rate limit.
     * @param string $identifier The unique identifier for the request (e.g., IP address).
     * @return bool Returns true if the request is allowed, false otherwise.
     * @throws RuntimeException If Redis commands fail to execute.
     */
    public function allow(string $key, string $identifier): bool
    {
        $key = $this->getKey($key, $identifier);

        $this->redis->multi();
        $this->redis->incr($key);
        $this->redis->expire($key, $this->decayMinutes * 60);
        $result = $this->redis->exec();

        if ($result === false) {
            throw new RuntimeException('Failed to execute Redis commands');
        }

        $attempts = $result[0];

        return $attempts <= $this->maxAttempts;
    }

    /**
     * Generates a unique Redis key for the rate limit.
     *
     * @param string $key The base key for the rate limit.
     * @param string $identifier The unique identifier for the request.
     * @return string The generated Redis key.
     */
    private function getKey(string $key, string $identifier): string
    {
        return "ratelimit:{$key}:{$identifier}";
    }
}
