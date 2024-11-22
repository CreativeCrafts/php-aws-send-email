<?php

namespace CreativeCrafts\EmailService\Interfaces;

interface TemplateInterface
{
    public function render(array $variables): string;
}
