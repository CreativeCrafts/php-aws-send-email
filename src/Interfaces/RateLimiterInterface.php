<?php

declare(strict_types=1);

namespace CreativeCrafts\EmailService\Interfaces;

interface RateLimiterInterface
{
    public function allow(string $key, string $identifier): bool;
}
