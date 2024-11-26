<?php

namespace CreativeCrafts\EmailService\Exceptions;

final class MissingRequiredParameterException extends \DomainException
{
    public function __construct(string $message = 'Missing required parameter.', int $code = 500)
    {
        parent::__construct($message, $code);
    }
}