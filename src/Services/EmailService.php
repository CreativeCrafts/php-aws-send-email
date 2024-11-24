<?php

declare(strict_types=1);

namespace CreativeCrafts\EmailService\Services;

use Aws\Exception\AwsException;
use Aws\Result;
use Aws\Ses\SesClient;
use CreativeCrafts\EmailService\Interfaces\EmailServiceInterface;
use CreativeCrafts\EmailService\Interfaces\RateLimiterInterface;
use CreativeCrafts\EmailService\Interfaces\TemplateEngineInterface;
use GuzzleHttp\Promise\PromiseInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Random\RandomException;
use RuntimeException;

class EmailService implements EmailServiceInterface
{
    private const MAX_ATTACHMENT_SIZE = 10 * 1024 * 1024; // 10 MB
    private const ALLOWED_MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];

    protected string $returnPath = '';

    private SesClient $sesClient;
    private LoggerInterface $logger;
    private ?RateLimiterInterface $rateLimiter;
    private ?TemplateEngineInterface $templateEngine;

    private string $senderEmail = '';
    private string $senderName = '';
    private string $recipientEmail = '';
    private string $subject = '';
    private string $bodyText = '';
    private string $bodyHtml = '';
    private array $attachments = [];

    private string $boundary;
    private array $messageBody = [];
    private array $emailHeaders = [];

    /**
     * Constructor for EmailService.
     *
     * @throws RandomException
     */
    public function __construct(
        SesClient $sesClient,
        ?LoggerInterface $logger = null,
        ?RateLimiterInterface $rateLimiter = null,
        ?TemplateEngineInterface $templateEngine = null
    ) {
        $this->sesClient = $sesClient;
        $this->logger = $logger ?? new NullLogger();
        $this->rateLimiter = $rateLimiter;
        $this->templateEngine = $templateEngine;
        $this->boundary = '=_Part_' . bin2hex(random_bytes(16));
    }

    /**
     * Sets the sender's email address.
     */
    public function setSenderEmail(string $email): self
    {
        $this->senderEmail = $email;
        return $this;
    }

    /**
     * Sets the sender's name.
     */
    public function setSenderName(string $name): self
    {
        $this->senderName = $name;
        return $this;
    }

    /**
     * Sets the recipient's email address.
     */
    public function setRecipientEmail(string $email): self
    {
        $this->recipientEmail = $email;
        return $this;
    }

    /**
     * Sets the email subject.
     */
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Sets the plain text body of the email.
     */
    public function setBodyText(string $bodyText): self
    {
        $this->bodyText = $bodyText;
        return $this;
    }

    /**
     * Sets the HTML body of the email.
     */
    public function setBodyHtml(string $bodyHtml): self
    {
        $this->bodyHtml = $bodyHtml;
        return $this;
    }

    /**
     * Sets the email template.
     */
    public function setEmailTemplate(string $templateName, array $variables = []): self
    {
        if (!$this->templateEngine) {
            throw new RuntimeException('Template engine not set.');
        }

        $template = $this->templateEngine->load($templateName);
        $renderedContent = $template->render($variables);

        // Assuming the template provides both text and HTML versions
        // Adjust as needed based on your template engine's implementation
        $this->bodyText = strip_tags($renderedContent);
        $this->bodyHtml = $renderedContent;

        return $this;
    }

    /**
     * Adds an attachment to the email.
     */
    public function addAttachment(string $filePath): self
    {
        $this->validateAttachment($filePath);
        $this->attachments[] = $filePath;
        return $this;
    }

    /**
     * Validates an attachment file for email sending.
     */
    private function validateAttachment(string $filePath): void
    {
        if (!file_exists($filePath)) {
            throw new InvalidArgumentException('Attachment file does not exist: ' . $filePath);
        }

        $fileSize = filesize($filePath);
        if ($fileSize === false || $fileSize > self::MAX_ATTACHMENT_SIZE) {
            throw new InvalidArgumentException('Attachment file size exceeds the maximum allowed size of 10 MB.');
        }

        $mimeType = mime_content_type($filePath);
        if ($mimeType === false || !in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException('Attachment file type is not allowed: ' . ($mimeType ?: 'Unknown'));
        }
    }

    /**
     * Sends an email using Amazon SES.
     */
    public function sendEmail(): Result
    {
        if ($this->rateLimiter instanceof RateLimiterInterface && !$this->rateLimiter->allow(
                'send_email',
                $this->senderEmail
            )) {
            throw new RuntimeException('Email sending rate limit exceeded');
        }

        $this->fullEmailDataValidation();
        $this->setEmailHeaders();
        $this->constructMessageBody();

        // Combine headers and body with proper line breaks
        $rawMessage = implode("\r\n", $this->emailHeaders) . "\r\n\r\n" . implode("\r\n", $this->messageBody);

        try {
            $result = $this->sesClient->sendRawEmail([
                'RawMessage' => [
                    'Data' => $rawMessage,
                ],
                'Source' => $this->getFormattedSender(),
                // Optionally, include 'ReturnPath' if needed
                // 'ReturnPath' => $this->returnPath,
            ]);
            $this->logger->info('Email sent successfully', [
                'messageId' => $result->get('MessageId'),
            ]);
            return $result;
        } catch (AwsException $exception) {
            $this->logger->error('Error sending email', [
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        }
    }

    /**
     * Validates that all required email data is set.
     */
    private function fullEmailDataValidation(): void
    {
        if (empty($this->senderEmail)) {
            throw new RuntimeException('Sender email is not set.');
        }

        if (empty($this->recipientEmail)) {
            throw new RuntimeException('Recipient email is not set.');
        }

        if (empty($this->subject)) {
            throw new RuntimeException('Email subject is not set.');
        }

        if (empty($this->bodyText) && empty($this->bodyHtml)) {
            throw new RuntimeException('Email body is not set.');
        }
    }

    /**
     * Sets the email headers.
     */
    protected function setEmailHeaders(): void
    {
        // Initialize headers as an indexed array of strings
        $this->emailHeaders = [
            "From: {$this->getFormattedSender()}",
            "Reply-To: {$this->getFormattedSender()}",
            "To: {$this->recipientEmail}",
            "Subject: {$this->encodeHeader($this->subject)}",
            "MIME-Version: 1.0",
        ];
    }

    /**
     * Returns the formatted sender (with name if available).
     */
    private function getFormattedSender(): string
    {
        if ($this->senderName) {
            // Encode the sender name to handle special characters
            $encodedName = $this->encodeHeader($this->senderName);
            return "{$encodedName} <{$this->senderEmail}>";
        }

        return $this->senderEmail;
    }

    /**
     * Encodes a header value if it contains non-ASCII characters.
     */
    private function encodeHeader(string $value): string
    {
        if (preg_match('/[\x80-\xFF]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }

    /**
     * Constructs the message body for the email.
     *
     * @throws RandomException
     */
    protected function constructMessageBody(): void
    {
        if (!empty($this->attachments)) {
            $mixedBoundary = '=_Mixed_' . bin2hex(random_bytes(16));
            $alternativeBoundary = $this->boundary;

            // Update the Content-Type header to multipart/mixed
            $this->emailHeaders[] = "Content-Type: multipart/mixed; boundary=\"{$mixedBoundary}\"";

            $this->messageBody = [
                "--{$mixedBoundary}",
                "Content-Type: multipart/alternative; boundary=\"{$alternativeBoundary}\"",
                "",
            ];

            // Add the alternative parts (text and HTML)
            $this->constructAlternativePart();

            // Add attachments
            $this->processEmailAttachments($mixedBoundary);

            // End the mixed boundary
            $this->messageBody[] = "--{$mixedBoundary}--";
        } else {
            // No attachments, use multipart/alternative
            $this->constructAlternativePart();

            // Update the Content-Type header to multipart/alternative
            $this->emailHeaders[] = "Content-Type: multipart/alternative; boundary=\"{$this->boundary}\"";
        }
    }

    /**
     * Constructs the alternative part of the message body (text and HTML).
     */
    private function constructAlternativePart(): void
    {
        // Text part
        $this->messageBody[] = "--{$this->boundary}";
        $this->messageBody[] = "Content-Type: text/plain; charset=UTF-8";
        $this->messageBody[] = "Content-Transfer-Encoding: 7bit";
        $this->messageBody[] = "";
        $this->messageBody[] = $this->bodyText;
        $this->messageBody[] = "";

        // HTML part
        $this->messageBody[] = "--{$this->boundary}";
        $this->messageBody[] = "Content-Type: text/html; charset=UTF-8";
        $this->messageBody[] = "Content-Transfer-Encoding: 7bit";
        $this->messageBody[] = "";
        $this->messageBody[] = $this->bodyHtml;
        $this->messageBody[] = "";

        // End of alternative part
        $this->messageBody[] = "--{$this->boundary}--";
    }

    /**
     * Processes email attachments and adds them to the message body.
     */
    protected function processEmailAttachments(string $mixedBoundary): void
    {
        foreach ($this->attachments as $attachment) {
            $filename = basename($attachment);
            $mimeType = mime_content_type($attachment) ?: 'application/octet-stream';
            $fileContent = file_get_contents($attachment);

            if ($fileContent === false) {
                throw new RuntimeException('Failed to read attachment: ' . $attachment);
            }

            $this->messageBody[] = "--{$mixedBoundary}";
            $this->messageBody[] = "Content-Type: {$mimeType}; name=\"{$filename}\"";
            $this->messageBody[] = "Content-Disposition: attachment; filename=\"{$filename}\"";
            $this->messageBody[] = "Content-Transfer-Encoding: base64";
            $this->messageBody[] = "";
            $this->messageBody[] = chunk_split(base64_encode($fileContent));
        }
    }

    /**
     * Sends an email asynchronously using Amazon SES.
     *
     * @throws RandomException
     */
    public function sendEmailAsync(): PromiseInterface
    {
        if ($this->rateLimiter instanceof RateLimiterInterface && !$this->rateLimiter->allow(
                'send_email',
                $this->senderEmail
            )) {
            throw new RuntimeException('Email sending rate limit exceeded');
        }

        $this->fullEmailDataValidation();
        $this->setEmailHeaders();
        $this->constructMessageBody();

        // Combine headers and body with proper line breaks
        $rawMessage = implode("\r\n", $this->emailHeaders) . "\r\n\r\n" . implode("\r\n", $this->messageBody);

        return $this->sesClient->sendRawEmailAsync([
            'RawMessage' => [
                'Data' => $rawMessage,
            ],
            'Source' => $this->getFormattedSender(),
            // Optionally, include 'ReturnPath' if needed
            // 'ReturnPath' => $this->returnPath,
        ])->then(
            function ($result) {
                $this->logger->info('Email sent successfully', [
                    'messageId' => $result['MessageId'],
                ]);
                return $result;
            },
            function ($exception): void {
                $this->logger->error('Error sending email', [
                    'error' => $exception->getMessage(),
                ]);
                throw $exception;
            }
        );
    }
}
