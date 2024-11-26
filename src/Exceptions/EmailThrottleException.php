<?php

namespace CreativeCrafts\EmailService\Exceptions;

final class EmailThrottleException extends \DomainException
{
    public function __construct(string $message = 'Email sending rate limit exceeded.', int $code = 500)
    {
        parent::__construct($message, $code);
    }
}