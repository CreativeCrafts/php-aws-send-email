<?php

declare(strict_types=1);

namespace CreativeCrafts\EmailService\Services\Templates;

use CreativeCrafts\EmailService\Interfaces\TemplateInterface;
use RuntimeException;

class AdvancedTemplate implements TemplateInterface
{
    private string $templatePath;

    /**
     * Constructor for the AdvancedTemplate class.
     * Initializes a new instance of the AdvancedTemplate class with the specified template path.
     *
     * @param string $templatePath The file path to the template file to be used for rendering.
     */
    public function __construct(string $templatePath)
    {
        $this->templatePath = $templatePath;
    }

    /**
     * Renders the template with the provided variables.
     * This method includes the template file in a closure with a limited scope,
     * passes the variables as arguments, captures the output, and returns it as a string.
     *
     * @param array $variables An associative array of variables to be made available to the template.
     * @return string The rendered template content as a string.
     * @throws RuntimeException If the template file does not exist.
     */
    public function render(array $variables): string
    {
        if (!file_exists($this->templatePath)) {
            throw new RuntimeException("Template file does not exist: {$this->templatePath}");
        }

        $templateHelpers = [
            'templatePath' => $this->templatePath,
        ];

        $allVariables = array_merge($variables, $templateHelpers);

        ob_start();
        (function ($__templatePath, $__variables): void {
            foreach ($__variables as $key => $value) {
                $$key = $value;
            }
            include $__templatePath;
        })(
            $this->templatePath,
            $allVariables
        );
        $content = ob_get_clean();

        if ($content === false) {
            throw new RuntimeException("Failed to capture output buffer while rendering template.");
        }

        return $content;
    }
}
