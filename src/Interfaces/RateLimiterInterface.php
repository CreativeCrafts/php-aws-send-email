<?php

namespace CreativeCrafts\EmailService\Interfaces;

interface RateLimiterInterface
{
    public function allow(string $key, string $identifier): bool;
}
