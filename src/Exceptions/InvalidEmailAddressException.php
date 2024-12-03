<?php

declare(strict_types=1);

namespace CreativeCrafts\EmailService\Exceptions;

use DomainException;

final class InvalidEmailAddressException extends DomainException
{
    public function __construct(string $message = 'Invalid email address provided.', int $code = 500)
    {
        parent::__construct($message, $code);
    }
}
