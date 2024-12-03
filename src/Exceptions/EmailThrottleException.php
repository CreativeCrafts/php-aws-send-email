<?php

declare(strict_types=1);

namespace CreativeCrafts\EmailService\Exceptions;

use DomainException;

final class EmailThrottleException extends DomainException
{
    public function __construct(string $message = 'Email sending rate limit exceeded.', int $code = 500)
    {
        parent::__construct($message, $code);
    }
}
