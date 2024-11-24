<?php

declare(strict_types=1);

namespace CreativeCrafts\EmailService\Interfaces;

use Aws\Result;
use GuzzleHttp\Promise\PromiseInterface;

interface EmailServiceInterface
{
    public function setSenderEmail(string $email): self;

    public function setSenderName(string $name): self;

    public function setRecipientEmail(string $email): self;

    public function setSubject(string $subject): self;

    public function setBodyText(string $bodyText): self;

    public function setBodyHtml(string $bodyHtml): self;

    public function addAttachment(string $filePath): self;

    public function setEmailTemplate(string $templateName, array $variables): self;

    public function send(): Result;

    public function sendEmailAsync(): PromiseInterface;
}
