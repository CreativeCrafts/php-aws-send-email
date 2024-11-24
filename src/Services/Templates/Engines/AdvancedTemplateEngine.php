<?php

declare(strict_types=1);

namespace CreativeCrafts\EmailService\Services\Templates\Engines;

use CreativeCrafts\EmailService\Interfaces\TemplateEngineInterface;
use CreativeCrafts\EmailService\Interfaces\TemplateInterface;
use CreativeCrafts\EmailService\Services\Templates\AdvancedTemplate;
use InvalidArgumentException;

/**
 * AdvancedTemplateEngine class for loading and managing email templates.
 */
class AdvancedTemplateEngine implements TemplateEngineInterface
{
    private string $templateDir;

    private string $partialDir;

    private string $templateExtension;

    private array $globalVariables = [];

    /**
     * Constructor for AdvancedTemplateEngine.
     *
     * @param string $templateDir The directory path where main template files are stored.
     * @param string $partialDir The directory path where partial template files are stored.
     * @param string $templateExtension The file extension for template files (default is '.html').
     * @param array $globalVariables An array of global variables available to all templates.
     * @throws InvalidArgumentException If the specified template or partial directory does not exist.
     */
    public function __construct(
        string $templateDir,
        string $partialDir,
        string $templateExtension = '.html',
        array $globalVariables = []
    ) {
        if (! is_dir($templateDir)) {
            throw new InvalidArgumentException("Template directory does not exist: $templateDir");
        }
        if (! is_dir($partialDir)) {
            throw new InvalidArgumentException("Partial directory does not exist: $partialDir");
        }
        $this->templateDir = rtrim($templateDir, '/\\');
        $this->partialDir = rtrim($partialDir, '/\\');
        $this->templateExtension = str_contains(
            $templateExtension,
            '.'
        ) ? $templateExtension : '.' . $templateExtension;
        $this->globalVariables = $globalVariables;
    }

    /**
     * Loads a template by its name.
     *
     * @param string $templateName The name of the template to load (without file extension).
     * @return TemplateInterface An instance of AdvancedTemplate representing the loaded template.
     * @throws InvalidArgumentException If the template file does not exist.
     */
    public function load(string $templateName): TemplateInterface
    {
        $templatePath = $this->templateDir . DIRECTORY_SEPARATOR . $templateName . $this->templateExtension;
        if (! file_exists($templatePath)) {
            throw new InvalidArgumentException("Template file does not exist: $templatePath");
        }
        return new AdvancedTemplate(
            $templatePath,
            $this->partialDir,
            $this->templateExtension,
            $this->globalVariables
        );
    }
}
