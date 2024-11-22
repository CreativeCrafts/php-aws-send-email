<?php

declare(strict_types=1);

namespace CreativeCrafts\EmailService\Interfaces;

interface TemplateEngineInterface
{
    public function load(string $templateName): TemplateInterface;
}
