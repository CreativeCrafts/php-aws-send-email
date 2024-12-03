<?php

declare(strict_types=1);

namespace CreativeCrafts\EmailService\Exceptions;

use DomainException;

final class InvalidAttachmentException extends DomainException
{
    public function __construct(string $message = 'Invalid attachment provided.', int $code = 500)
    {
        parent::__construct($message, $code);
    }
}
