<?php

declare(strict_types=1);

namespace CreativeCrafts\EmailService\Services;

use Aws\Exception\AwsException;
use Aws\Result;
use Aws\Ses\SesClient;
use CreativeCrafts\EmailService\Interfaces\EmailServiceInterface;
use CreativeCrafts\EmailService\Interfaces\RateLimiterInterface;
use CreativeCrafts\EmailService\Interfaces\TemplateEngineInterface;
use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Random\RandomException;
use RuntimeException;

class EmailService implements EmailServiceInterface
{
    private const MAX_ATTACHMENT_SIZE = 10 * 1024 * 1024;

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
     * Initializes the EmailService with required dependencies.
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
     * Sets the return path for the email.
     * This method sets the return path (also known as the bounce address) for the email.
     * It validates the provided email address before setting it.
     *
     * @param string $returnPath The email address to be used as the return path.
     * @return self Returns the current instance to allow method chaining.
     * @throws InvalidArgumentException If the provided email address is invalid.
     */
    public function setReturnPath(string $returnPath): self
    {
        if (!$this->validateEmail($returnPath)) {
            throw new InvalidArgumentException('Invalid email address');
        }
        $this->returnPath = $returnPath;
        return $this;
    }

    /**
     * Validates an email address.
     * This method checks if the given email address is valid by using PHP's built-in
     * filter_var function and verifying the existence of an MX record for the domain.
     *
     * @param string $email The email address to validate.
     * @return bool Returns true if the email is valid and has a valid MX record,
     *              false otherwise.
     */
    protected function validateEmail(string $email): bool
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $domain = strrchr($email, "@");
        if ($domain === false) {
            return false;
        }

        $domain = substr($domain, 1);
        return checkdnsrr($domain, "MX");
    }

    /**
     * Sets the sender's email address for the email.
     * This method validates the provided email address before setting it.
     * If the email is invalid, an exception is thrown.
     *
     * @param string $email The email address of the sender.
     * @return self Returns the current instance of the class for method chaining.
     * @throws InvalidArgumentException If the provided email is invalid.
     */
    public function setSenderEmail(string $email): self
    {
        if (!$this->validateEmail($email)) {
            throw new InvalidArgumentException('Invalid sender email');
        }
        $this->senderEmail = $email;
        return $this;
    }

    /**
     * Sets the sender's name for the email.
     * This method sanitizes the provided name to prevent XSS attacks and sets it as the sender's name.
     * It uses htmlspecialchars to convert special characters to HTML entities.
     *
     * @param string $name The name of the sender to be set.
     * @return self Returns the current instance of the class for method chaining.
     */
    public function setSenderName(string $name): self
    {
        $this->senderName = htmlspecialchars($name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $this;
    }

    /**
     * Sets the recipient's email address for the email.
     * This method validates the provided email address before setting it as the recipient's email.
     * If the email is invalid, an exception is thrown.
     *
     * @param string $email The email address of the recipient.
     * @return self Returns the current instance of the class for method chaining.
     * @throws InvalidArgumentException If the provided email is invalid.
     */
    public function setRecipientEmail(string $email): self
    {
        if (!$this->validateEmail($email)) {
            throw new InvalidArgumentException('Invalid recipient email');
        }
        $this->recipientEmail = $email;
        return $this;
    }

    /**
     * Sets the subject of the email.
     * This method sanitizes the provided subject to prevent XSS attacks
     * by converting special characters to HTML entities using htmlspecialchars.
     *
     * @param string $subject The subject line for the email.
     * @return self Returns the current instance of the class for method chaining.
     */
    public function setSubject(string $subject): self
    {
        $this->subject = htmlspecialchars($subject, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $this;
    }

    /**
     * Adds an attachment to the email.
     * This method validates the attachment file and adds it to the list of attachments
     * to be sent with the email. It performs checks on file existence, size, and type.
     *
     * @param string $filePath The full path to the attachment file.
     * @return self Returns the current instance of the class for method chaining.
     * @throws InvalidArgumentException If the file doesn't exist, exceeds size limit, or has an unsupported type.
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
        if ($fileSize > self::MAX_ATTACHMENT_SIZE) {
            throw new InvalidArgumentException('Attachment file size exceeds the maximum allowed size');
        }

        $mimeType = mime_content_type($filePath);
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidArgumentException('Attachment file type is not allowed: ' . $mimeType);
        }
    }

    /**
     * Sets the email template and renders it with the provided variables.
     * This method loads a template, renders it with the given variables,
     * sets the rendered content as the HTML body of the email, and
     * generates a plain text version of the body.
     *
     * @param string $templateName The name or identifier of the email template to load.
     * @param array $variables An associative array of variables to be used in rendering the template.
     * @return self Returns the current instance of the class for method chaining.
     * @throws RuntimeException If the template engine is not set.
     */
    public function setEmailTemplate(string $templateName, array $variables): self
    {
        if (!$this->templateEngine instanceof TemplateEngineInterface) {
            throw new RuntimeException('Template engine is not set');
        }
        try {
            $template = $this->templateEngine->load($templateName);
            $renderedHtml = $template->render($variables);

            $this->setBodyHtml($renderedHtml);
            $this->setBodyText($this->htmlToText($renderedHtml));
        } catch (Exception $e) {
            throw new RuntimeException('Error rendering template: ' . $e->getMessage(), $e->getCode(), $e);
        }

        return $this;
    }

    /**
     * Sets the HTML body of the email.
     * This method sanitizes the provided HTML content to prevent XSS attacks
     * by using PHP's filter_var function with FILTER_SANITIZE_FULL_SPECIAL_CHARS.
     *
     * @param string $bodyHtml The HTML content for the email body.
     * @return self Returns the current instance of the class for method chaining.
     */
    public function setBodyHtml(string $bodyHtml): self
    {
        $this->bodyHtml = $bodyHtml;
        return $this;
    }

    /**
     * Sets the plain text body of the email.
     * This method sanitizes the provided text to prevent XSS attacks
     * by converting special characters to HTML entities using htmlspecialchars.
     *
     * @param string $bodyText The plain text content for the email body.
     * @return self Returns the current instance of the class for method chaining.
     */
    public function setBodyText(string $bodyText): self
    {
        $this->bodyText = $bodyText;
        return $this;
    }

    /**
     * Converts HTML content to plain text.
     * This function removes HTML tags, decodes HTML entities, and normalizes whitespace
     * to create a plain text version of the given HTML content.
     *
     * @param string $html The HTML content to be converted to plain text.
     * @return string The resulting plain text version of the input HTML.
     */
    private function htmlToText(string $html): string
    {
        /** @var string $html */
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html);
        /** @var string $html */
        $html = preg_replace('/<\/p>/i', "\n\n", $html);
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim($text);
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

        $rawMessage = implode("\r\n", $this->emailHeaders) . "\r\n\r\n" . implode("\r\n", $this->messageBody);

        try {
            $result = $this->sesClient->sendRawEmail([
                'RawMessage' => [
                    'Data' => $rawMessage,
                ],
                'Source' => $this->source(),
                'ReturnPath' => $this->returnPath,
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
     * Performs full validation of email data before sending.
     * This method checks the validity of the sender and recipient email addresses,
     * ensures that a subject is provided, and verifies that either a text or HTML body
     * is set for the email.
     *
     * @throws InvalidArgumentException If any of the required email data is missing or invalid:
     *                                  - Invalid sender email
     *                                  - Invalid recipient email
     *                                  - Missing subject
     *                                  - Missing both text and HTML body
     */
    protected function fullEmailDataValidation(): void
    {
        if (!$this->validateEmail($this->senderEmail)) {
            throw new InvalidArgumentException('A valid sender email is required');
        }
        if (!$this->validateEmail($this->recipientEmail)) {
            throw new InvalidArgumentException('A valid recipient email is required');
        }
        if ($this->subject === '' || $this->subject === '0') {
            throw new InvalidArgumentException('Subject is required');
        }
        if (($this->bodyText === '' || $this->bodyText === '0') && ($this->bodyHtml === '' || $this->bodyHtml === '0')) {
            throw new InvalidArgumentException('Either text or html body must be set');
        }
    }

    /**
     * Sets the email headers for the message.
     */
    protected function setEmailHeaders(): void
    {
        $this->emailHeaders = [
            'From' => $this->source(),
            'Reply-To' => $this->source(),
            'To' => $this->recipientEmail,
            'Subject' => $this->encodeHeader($this->subject),
            'MIME-Version' => '1.0',
        ];
    }

    /**
     * Generates the 'From' field for the email header.
     * This method constructs the 'From' field of the email header. If a sender name
     * is set, it combines the name and email address in the format "Name <email@example.com>".
     * If no sender name is set, it returns just the email address.
     *
     * @return string The formatted 'From' field for the email header.
     *                If sender name is set: "Sender Name <sender@example.com>"
     *                If sender name is not set: "sender@example.com"
     */
    protected function source(): string
    {
        if ($this->senderName !== '' && $this->senderName !== '0') {
            $encodedName = '=?UTF-8?B?' . base64_encode($this->senderName) . '?=';
            return sprintf('%s <%s>', $encodedName, $this->senderEmail);
        }
        return $this->senderEmail;
    }

    /**
     * Encodes a header value if necessary.
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
        $mixedBoundary = '=_Mixed_' . bin2hex(random_bytes(16));
        $alternativeBoundary = $this->boundary;

        $this->messageBody = [];

        if ($this->attachments !== []) {
            // Start the mixed boundary
            $this->emailHeaders['Content-Type'] = 'multipart/mixed; boundary="' . $mixedBoundary . '"';

            $this->messageBody[] = '--' . $mixedBoundary;

            // Create the alternative part
            $this->messageBody[] = 'Content-Type: multipart/alternative; boundary="' . $alternativeBoundary . '"';
            $this->messageBody[] = '';

            $this->constructAlternativePart();

            // Attachments
            $this->processEmailAttachments($mixedBoundary);

            // End the mixed boundary
            $this->messageBody[] = '--' . $mixedBoundary . '--';
        } else {
            // No attachments, use alternative boundary
            $this->constructAlternativePart();
            $this->emailHeaders['Content-Type'] = 'multipart/alternative; boundary="' . $alternativeBoundary . '"';
        }
    }

    /**
     * Constructs the alternative part of the message body (text and HTML).
     */
    private function constructAlternativePart(): void
    {
        // Text part
        $this->messageBody[] = '--' . $this->boundary;
        $this->messageBody[] = 'Content-Type: text/plain; charset=UTF-8';
        $this->messageBody[] = 'Content-Transfer-Encoding: 7bit';
        $this->messageBody[] = '';
        $this->messageBody[] = $this->bodyText;
        $this->messageBody[] = '';

        // HTML part
        $this->messageBody[] = '--' . $this->boundary;
        $this->messageBody[] = 'Content-Type: text/html; charset=UTF-8';
        $this->messageBody[] = 'Content-Transfer-Encoding: 7bit';
        $this->messageBody[] = '';
        $this->messageBody[] = $this->bodyHtml;
        $this->messageBody[] = '';

        // End of alternative part
        $this->messageBody[] = '--' . $this->boundary . '--';
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

            $this->messageBody[] = '--' . $mixedBoundary;
            $this->messageBody[] = 'Content-Type: ' . $mimeType . '; name="' . $filename . '"';
            $this->messageBody[] = 'Content-Disposition: attachment; filename="' . $filename . '"';
            $this->messageBody[] = 'Content-Transfer-Encoding: base64';
            $this->messageBody[] = '';
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

        $rawMessage = implode("\r\n", $this->emailHeaders) . "\r\n\r\n" . implode("\r\n", $this->messageBody);

        return $this->sesClient->sendRawEmailAsync([
            'RawMessage' => [
                'Data' => $rawMessage,
            ],
            'Source' => $this->source(),
            'ReturnPath' => $this->returnPath,
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
