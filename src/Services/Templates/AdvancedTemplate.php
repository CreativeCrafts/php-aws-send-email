<?php

declare(strict_types=1);

namespace CreativeCrafts\EmailService\Services\Templates;

use CreativeCrafts\EmailService\Interfaces\TemplateInterface;

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
     */
    public function render(array $variables): string
    {
        ob_start();
        (static function ($__templatePath, $__variables) {
            foreach ($__variables as $__key => $__value) {
                $$__key = $__value;
            }
            include $__templatePath;
        })(
            $this->templatePath,
            $variables
        );
        return ob_get_clean() ?: '';
    }
}
