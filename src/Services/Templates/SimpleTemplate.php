<?php

declare(strict_types=1);

namespace CreativeCrafts\EmailService\Services\Templates;

use CreativeCrafts\EmailService\Interfaces\TemplateInterface;

/**
 * SimpleTemplate class for rendering simple templates with variable substitution.
 */
class SimpleTemplate implements TemplateInterface
{
    private string $content;

    /**
     * Constructs a new SimpleTemplate instance.
     *
     * @param string $content The template content.
     */
    public function __construct(string $content)
    {
        $this->content = $content;
    }

    /**
     * Renders the template by replacing placeholders with provided variables.
     *
     * @param array $variables An associative array of variables to be replaced in the template.
     * @return string The rendered template with variables replaced.
     */
    public function render(array $variables): string
    {
        return strtr($this->content, $this->prepareVariables($variables));
    }

    /**
     * Prepares variables for use in the template by adding curly braces and escaping values.
     *
     * @param array $variables An associative array of variables to be prepared.
     * @return array An array with keys wrapped in curly braces and values HTML-escaped.
     */
    private function prepareVariables(array $variables): array
    {
        $prepared = [];
        foreach ($variables as $key => $value) {
            $prepared['{' . $key . '}'] = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        }
        return $prepared;
    }
}
