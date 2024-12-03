<?php

declare(strict_types=1);

namespace CreativeCrafts\EmailService\Exceptions;

use DomainException;

final class MissingRequiredParameterException extends DomainException
{
    public function __construct(string $message = 'Missing required parameter.', int $code = 500)
    {
        parent::__construct($message, $code);
    }
}
