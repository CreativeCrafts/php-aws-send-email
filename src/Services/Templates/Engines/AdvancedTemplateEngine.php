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
    private string $templateExtension;

    /**
     * Constructor for AdvancedTemplateEngine.
     * Initializes the template engine with the specified template directory.
     *
     * @param string $templateDir The directory path where template files are stored.
     * @param string $templateExtension The file extension for template files (default is 'php').
     * @throws InvalidArgumentException If the specified template directory does not exist.
     */
    public function __construct(string $templateDir, string $templateExtension = '.html')
    {
        if (!is_dir($templateDir)) {
            throw new InvalidArgumentException("Template directory does not exist: $templateDir");
        }
        $this->templateDir = rtrim($templateDir, '/\\');
        $this->templateExtension = !str_contains(
            $templateExtension,
            '.'
        ) ? '.' . $templateExtension : $templateExtension;
    }

    /**
     * Loads a template by its name.
     * This method constructs the full path to the template file and returns an AdvancedTemplate instance.
     *
     * @param string $templateName The name of the template to load (without file extension).
     * @return TemplateInterface An instance of AdvancedTemplate representing the loaded template.
     * @throws InvalidArgumentException If the template file does not exist.
     */
    public function load(string $templateName): TemplateInterface
    {
        $templatePath = $this->templateDir . DIRECTORY_SEPARATOR . $templateName . $this->templateExtension;
        if (!file_exists($templatePath)) {
            throw new InvalidArgumentException("Template file does not exist: $templatePath");
        }
        return new AdvancedTemplate($templatePath);
    }
}