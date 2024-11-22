<?php

declare(strict_types=1);

namespace CreativeCrafts\EmailService\Interfaces;

interface TemplateInterface
{
    public function render(array $variables): string;
}
