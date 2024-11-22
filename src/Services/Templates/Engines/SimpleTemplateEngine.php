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

    /**
     * Constructor for SimpleTemplateEngine.
     *
     * @param string $templateDir The directory path where template files are stored.
     * @throws InvalidArgumentException If the specified template directory does not exist.
     */
    public function __construct(string $templateDir)
    {
        if (! is_dir($templateDir)) {
            throw new InvalidArgumentException("Template directory does not exist: $templateDir");
        }
        $this->templateDir = rtrim($templateDir, '/\\');
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
        $templatePath = $this->templateDir . DIRECTORY_SEPARATOR . $templateName . '.html';
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
