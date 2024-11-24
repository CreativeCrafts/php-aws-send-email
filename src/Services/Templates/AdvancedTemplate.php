<?php

declare(strict_types=1);

namespace CreativeCrafts\EmailService\Services\Templates;

use CreativeCrafts\EmailService\Interfaces\TemplateInterface;
use RuntimeException;

class AdvancedTemplate implements TemplateInterface
{
    private string $templatePath;

    private string $partialDir;

    private string $templateExtension;

    private array $variables = [];

    /**
     * Constructor for the AdvancedTemplate class.
     *
     * @param string $templatePath The file path to the template file to be used for rendering.
     * @param string $partialDir The directory path where partial templates are stored.
     */
    public function __construct(string $templatePath, string $partialDir, string $templateExtension = '.html')
    {
        $this->templatePath = $templatePath;
        $this->partialDir = rtrim($partialDir, '/\\');
        $this->templateExtension = str_contains(
            $templateExtension,
            '.'
        ) ? $templateExtension : '.' . $templateExtension;
    }

    /**
     * Renders the template with the provided variables.
     *
     * @param array $variables An associative array of variables to be made available to the template.
     * @return string The rendered template content as a string.
     * @throws RuntimeException If the template file does not exist.
     */
    public function render(array $variables): string
    {
        if (! file_exists($this->templatePath)) {
            throw new RuntimeException("Template file does not exist: {$this->templatePath}");
        }

        $this->variables = $variables;

        ob_start();
        $this->includeTemplate($this->templatePath, $this->variables);
        $content = ob_get_clean();

        if ($content === false) {
            throw new RuntimeException("Failed to capture output buffer while rendering template.");
        }

        return $content;
    }

    /**
     * Includes a template file with variables in a secure way.
     *
     * @param string $templatePath The path to the template file.
     * @param array $variables Variables to be available in the template.
     */
    private function includeTemplate(string $templatePath, array $variables): void
    {
        // Make variables available to the included template
        foreach ($variables as $key => $value) {
            $$key = $value;
        }

        include $templatePath;
    }

    /**
     * Renders a partial template.
     *
     * @param string $partialName The name of the partial template file (without extension).
     * @param array $variables Variables to pass to the partial template.
     * @return string The rendered partial template content.
     * @throws RuntimeException If the partial template file does not exist.
     */
    public function partial(string $partialName, array $variables = []): string
    {
        $partialPath = $this->partialDir . DIRECTORY_SEPARATOR . $partialName . $this->templateExtension;

        if (! file_exists($partialPath)) {
            throw new RuntimeException("Partial template file does not exist: {$partialPath}");
        }

        // Merge the main template variables with the partial-specific variables
        $mergedVariables = array_merge($this->variables, $variables);

        ob_start();
        $this->includeTemplate($partialPath, $mergedVariables);
        $content = ob_get_clean();

        if ($content === false) {
            throw new RuntimeException("Failed to capture output buffer while rendering partial template.");
        }

        return $content;
    }

    /**
     * Magic method to access variables as properties.
     *
     * @param string $name The name of the variable to access.
     * @return mixed The value of the variable or null if it doesn't exist.
     */
    public function __get(string $name): mixed
    {
        return $this->variables[$name] ?? null;
    }

    /**
     * Magic method to set variables as properties.
     *
     * @param string $name The name of the variable to set.
     * @param mixed $value The value to set for the variable.
     */
    public function __set(string $name, mixed $value): void
    {
        $this->variables[$name] = $value;
    }

    /**
     * Magic method to check if a variable is set.
     *
     * @param string $name The name of the variable to check.
     * @return bool True if the variable is set, false otherwise.
     */
    public function __isset(string $name): bool
    {
        return isset($this->variables[$name]);
    }
}
