<?php

declare(strict_types=1);

namespace CreativeCrafts\EmailService\Exceptions;

use DomainException;

final class TemplateEngineNotSetException extends DomainException
{
    public function __construct(string $message = 'Template engine not set. It is required.', int $code = 500)
    {
        parent::__construct($message, $code);
    }
}
