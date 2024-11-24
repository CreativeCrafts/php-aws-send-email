<?php

declare(strict_types=1);

namespace CreativeCrafts\EmailService\Services\Templates\Engines;

use CreativeCrafts\EmailService\Interfaces\TemplateEngineInterface;
use CreativeCrafts\EmailService\Interfaces\TemplateInterface;
use CreativeCrafts\EmailService\Services\Templates\SimpleTemplate;
use InvalidArgumentException;

/**
 * SimpleTemplateEngine class for loading and managing simple HTML templates.
 */
class SimpleTemplateEngine implements TemplateEngineInterface
{
    private string $templateDir;

    private string $templateExtension;

    /**
     * Initializes the SimpleTemplateEngine with a template directory and file extension.
     * This constructor sets up the engine by specifying where to find templates and what file extension they use.
     * It validates the provided template directory and normalizes the path.
     *
     * @param string $templateDir The directory where template files are stored.
     * @param string $templateExtension The file extension for template files (default is 'html').
     * @throws InvalidArgumentException If the specified template directory does not exist.
     */
    public function __construct(string $templateDir, string $templateExtension = '.html')
    {
        if (! is_dir($templateDir)) {
            throw new InvalidArgumentException("Template directory does not exist: $templateDir");
        }
        $this->templateDir = rtrim($templateDir, '/\\');
        $this->templateExtension = str_contains(
            $templateExtension,
            '.'
        ) ? $templateExtension : '.' . $templateExtension;
    }

    /**
     * Loads a template file and returns it as a TemplateInterface instance.
     *
     * @param string $templateName The name of the template file (without .html extension).
     * @return TemplateInterface An instance of SimpleTemplate containing the loaded template content.
     * @throws InvalidArgumentException If the specified template file does not exist.
     */
    public function load(string $templateName): TemplateInterface
    {
        $templatePath = $this->templateDir . DIRECTORY_SEPARATOR . $templateName . $this->templateExtension;
        if (! file_exists($templatePath)) {
            throw new InvalidArgumentException("Template file does not exist: $templatePath");
        }

        $content = file_get_contents($templatePath);
        if ($content === false) {
            throw new InvalidArgumentException("Failed to read template file: $templatePath");
        }

        return new SimpleTemplate($content);
    }
}
