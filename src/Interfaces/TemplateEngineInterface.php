<?php

namespace CreativeCrafts\EmailService\Interfaces;

interface TemplateEngineInterface
{
    public function load(string $templateName): TemplateInterface;
}
