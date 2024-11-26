<?php

declare(strict_types=1);

use CreativeCrafts\EmailService\Services\EmailService;

pest()->project()->github('CreativeCrafts/php-aws-send-email');

function setupValidEmailData(EmailService $emailService): EmailService
{
    return $emailService
        ->setSenderEmail('sender@example.com')
        ->setRecipientEmail('recipient@example.com')
        ->setSubject('Test Subject')
        ->setBodyText('Test body')
        ->setBodyHtml('<p>Test body</p>');
}