<?php

declare(strict_types=1);

namespace CreativeCrafts\EmailService\Services\RateLimiter;

use CreativeCrafts\EmailService\Interfaces\RateLimiterInterface;

class InMemoryRateLimiter implements RateLimiterInterface
{
    private array $storage = [];

    private int $maxAttempts;

    private int $decaySeconds;

    /**
     * Initializes the InMemoryRateLimiter with the specified maximum attempts and decay time.
     *
     * @param int $maxAttempts The maximum number of attempts allowed within the decay period. Default is 5.
     * @param int $decaySeconds The number of seconds after which the rate limit resets. Default is 60.
     */
    public function __construct(int $maxAttempts = 5, int $decaySeconds = 60)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decaySeconds = $decaySeconds;
    }

    /**
     * Checks if the given key and identifier combination is allowed to perform an action based on the rate limit.
     * This method tracks the number of attempts for each unique key-identifier pair and determines
     * whether the current attempt should be allowed based on the configured max attempts and decay time.
     *
     * @param string $key The rate limit key, typically representing the action or resource being limited.
     * @param string $identifier A unique identifier for the entity being rate limited (e.g., user ID, IP address).
     * @return bool Returns true if the attempt is allowed, false if the rate limit has been exceeded.
     */
    public function allow(string $key, string $identifier): bool
    {
        $key = $this->getKey($key, $identifier);
        $now = time();

        if (! isset($this->storage[$key])) {
            $this->storage[$key] = [
                'attempts' => 0,
                'reset_at' => $now + $this->decaySeconds,
            ];
        }

        if ($this->storage[$key]['reset_at'] <= $now) {
            $this->storage[$key]['attempts'] = 0;
            $this->storage[$key]['reset_at'] = $now + $this->decaySeconds;
        }

        $this->storage[$key]['attempts']++;

        return $this->storage[$key]['attempts'] <= $this->maxAttempts;
    }

    /**
     * Generates a unique key for storing rate limit information.
     *
     * @param string $key The rate limit key.
     * @param string $identifier The unique identifier for the entity being rate limited.
     * @return string The combined key used for storing rate limit data.
     */
    private function getKey(string $key, string $identifier): string
    {
        return "ratelimit:{$key}:{$identifier}";
    }
}
