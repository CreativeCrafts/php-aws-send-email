<?php

declare(strict_types=1);

namespace CreativeCrafts\EmailService\Services;

use Aws\Exception\AwsException;
use Aws\Result;
use Aws\Ses\SesClient;
use CreativeCrafts\EmailService\Exceptions\EmailThrottleException;
use CreativeCrafts\EmailService\Exceptions\InvalidAttachmentException;
use CreativeCrafts\EmailService\Exceptions\InvalidEmailAddressException;
use CreativeCrafts\EmailService\Exceptions\MissingRequiredParameterException;
use CreativeCrafts\EmailService\Exceptions\TemplateEngineNotSetException;
use CreativeCrafts\EmailService\Interfaces\EmailServiceInterface;
use CreativeCrafts\EmailService\Interfaces\RateLimiterInterface;
use CreativeCrafts\EmailService\Interfaces\TemplateEngineInterface;
use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Random\RandomException;

class EmailService implements EmailServiceInterface
{
    private const MAX_ATTACHMENT_SIZE = 10 * 1024 * 1024; // 10 MB

    private const ALLOWED_MIME_TYPES = ['application/pdf', 'image/jpeg', 'image/png'];

    private string $returnPath = '';

    private string $bcc = '';

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
     * Initializes the EmailService with necessary dependencies and sets up the MIME boundary.
     *
     * @param SesClient $sesClient The Amazon SES client for sending emails.
     * @param LoggerInterface|null $logger Optional logger for recording operations. If null, a NullLogger is used.
     * @param RateLimiterInterface|null $rateLimiter Optional rate limiter to control email sending frequency.
     * @param TemplateEngineInterface|null $templateEngine Optional template engine for rendering email content.
     * @throws RandomException If there's an issue generating random bytes for the MIME boundary.
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
     * Sets the sender's email address for the email.
     * This method allows you to specify the email address that will appear
     * as the sender of the email. It's part of the fluent interface, allowing
     * method chaining.
     *
     * @param string $email The email address of the sender.
     * @return self Returns the current instance of the class for method chaining.
     */
    public function setSenderEmail(string $email): self
    {
        $this->validateEmail($email);
        $this->senderEmail = $email;
        return $this;
    }

    /**
     * Sets the sender's name for the email.
     * This method allows you to specify the name that will appear
     * as the sender of the email. It's part of the fluent interface,
     * allowing method chaining.
     *
     * @param string $name The name of the sender to be displayed in the email.
     * @return self Returns the current instance of the class for method chaining.
     */
    public function setSenderName(string $name): self
    {
        $this->senderName = $name;
        return $this;
    }

    /**
     * Sets the recipient's email address for the email.
     * This method allows you to specify the email address of the recipient.
     * It's part of the fluent interface, allowing method chaining.
     *
     * @param string $email The email address of the recipient.
     * @return self Returns the current instance of the class for method chaining.
     */
    public function setRecipientEmail(string $email): self
    {
        $this->validateEmail($email);
        $this->recipientEmail = $email;
        return $this;
    }

    public function setReturnPath(string $returnPath): self
    {
        $this->validateEmail($returnPath);
        $this->returnPath = $returnPath;
        return $this;
    }

    /**
     * Sets the BCC (Blind Carbon Copy) recipient for the email.
     * This method allows you to specify an email address that will receive a copy of the email
     * without other recipients being aware. It validates the provided email address before setting it.
     * This method is part of the fluent interface, allowing method chaining.
     *
     * @param string $bcc The email address to be set as BCC recipient.
     * @return self Returns the current instance of the class for method chaining.
     * @throws InvalidArgumentException If the provided email address is invalid.
     */
    public function setBcc(string $bcc): self
    {
        $this->validateEmail($bcc);
        $this->bcc = $bcc;
        return $this;
    }

    /**
     * Sets the email subject.
     * This method allows you to set the subject line for the email.
     * It's part of the fluent interface, allowing method chaining.
     *
     * @param string $subject The subject line for the email.
     * @return self Returns the current instance of the class for method chaining.
     */
    public function setSubject(string $subject): self
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Sets the plain text body of the email.
     * This method allows you to set the plain text content of the email body.
     * It's part of the fluent interface, enabling method chaining.
     *
     * @param string $bodyText The plain text content to be used as the email body.
     * @return self Returns the current instance of the class for method chaining.
     */
    public function setBodyText(string $bodyText): self
    {
        $this->bodyText = $bodyText;
        return $this;
    }

    /**
     * Sets the HTML body of the email.
     * This method allows you to set the HTML content of the email body.
     * It's part of the fluent interface, enabling method chaining.
     *
     * @param string $bodyHtml The HTML content to be used as the email body.
     * @return self Returns the current instance of the class for method chaining.
     */
    public function setBodyHtml(string $bodyHtml): self
    {
        $this->bodyHtml = $bodyHtml;
        return $this;
    }

    /**
     * Sets the email template and renders its content.
     * This method loads a specified email template, renders it with provided variables,
     * and sets both the plain text and HTML versions of the email body.
     *
     * @param string $templateName The name or identifier of the email template to be used.
     * @param array $variables An associative array of variables to be used in rendering the template. Default is an empty array.
     * @return self Returns the current instance of the class for method chaining.
     * @throws TemplateEngineNotSetException If the template engine is not set.
     */
    public function setEmailTemplate(string $templateName, array $variables = [], bool $isHtml = true): self
    {
        if (! $this->templateEngine instanceof TemplateEngineInterface) {
            throw new TemplateEngineNotSetException('Template engine not set.', 406);
        }

        $template = $this->templateEngine->load($templateName);
        $renderedContent = $template->render($variables);

        if ($isHtml) {
            $this->bodyHtml = $renderedContent;
            $this->bodyText = strip_tags($renderedContent);
        } else {
            $this->bodyText = $renderedContent;
            $this->bodyHtml = nl2br($renderedContent);
        }

        return $this;
    }

    /**
     * Adds an attachment to the email.
     * This method validates the attachment file and adds it to the list of attachments
     * to be included in the email. It uses a fluent interface pattern, allowing for method chaining.
     *
     * @param string $filePath The full path to the attachment file.
     * @return self Returns the current instance of the class for method chaining.
     * @throws InvalidArgumentException If the file doesn't exist, exceeds size limit, or has an unsupported MIME type.
     */
    public function addAttachment(string $filePath): self
    {
        $this->validateAttachment($filePath);
        $this->attachments[] = $filePath;
        return $this;
    }

    /**
     * Sends an email using Amazon SES.
     * This method performs rate limiting checks, validates email data, constructs the email message,
     * and sends it using Amazon SES. It logs the result of the operation and handles any exceptions.
     *
     * @return Result The result object from Amazon SES containing information about the sent email.
     * @throws AwsException If there's an error while sending the email through Amazon SES.
     * @throws RandomException If there's an issue generating random bytes for the MIME boundary.
     * @throws EmailThrottleException If the email sending rate limit is exceeded.
     */
    public function sendEmail(): Result
    {
        if ($this->rateLimiter instanceof RateLimiterInterface && ! $this->rateLimiter->allow(
            'send_email',
            $this->senderEmail
        )) {
            throw new EmailThrottleException('Email sending rate limit exceeded', 406);
        }

        $this->fullEmailDataValidation();
        $this->setEmailHeaders();
        $this->constructMessageBody();

        $rawMessage = implode("\r\n", $this->emailHeaders) . "\r\n\r\n" . implode("\r\n", $this->messageBody);

        try {
            $params = [
                'RawMessage' => [
                    'Data' => $rawMessage,
                ],
                'Source' => $this->getFormattedSender(),
                'ReturnPath' => $this->returnPath,
            ];

            if ($this->bcc !== '' && $this->bcc !== '0') {
                $params['Destinations'] = [$this->recipientEmail, $this->bcc];
            }

            $result = $this->sesClient->sendRawEmail($params);
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
     * Sends an email asynchronously using Amazon SES.
     * This method performs rate limiting checks, validates email data, constructs the email message,
     * and sends it asynchronously using Amazon SES. It logs the result of the operation and handles
     * any exceptions.
     *
     * @return PromiseInterface A promise that resolves with the result of the email sending operation.
     *                          The promise will be fulfilled with an array containing the 'MessageId'
     *                          on success, or rejected with an exception on failure.
     * @throws EmailThrottleException If the email sending rate limit is exceeded.
     * @throws RandomException If there's an issue generating random bytes for the MIME boundary.
     */
    public function sendEmailAsync(): PromiseInterface
    {
        if ($this->rateLimiter instanceof RateLimiterInterface && ! $this->rateLimiter->allow(
            'send_email',
            $this->senderEmail
        )) {
            throw new EmailThrottleException('Email sending rate limit exceeded', 406);
        }

        $this->fullEmailDataValidation();
        $this->setEmailHeaders();
        $this->constructMessageBody();

        // Combine headers and body with proper line breaks
        $rawMessage = implode("\r\n", $this->emailHeaders) . "\r\n\r\n" . implode("\r\n", $this->messageBody);

        $params = [
            'RawMessage' => [
                'Data' => $rawMessage,
            ],
            'Source' => $this->getFormattedSender(),
            'ReturnPath' => $this->returnPath,
        ];

        if ($this->bcc !== '' && $this->bcc !== '0') {
            $params['Destinations'] = [$this->recipientEmail, $this->bcc];
        }
        return $this->sesClient->sendRawEmailAsync($params)->then(
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

    /**
     * Sets the email headers for the message.
     * This method initializes the email headers as an indexed array of strings.
     * It sets the From, Reply-To, To, Subject, and MIME-Version headers.
     * The From and Reply-To headers use the formatted sender information.
     * The Subject is encoded to handle special characters.
     */
    protected function setEmailHeaders(): void
    {
        $this->emailHeaders = [
            'MIME-Version: 1.0',
            'From: ' . $this->getFormattedSender(),
            'To: ' . $this->recipientEmail,
            'Subject: =?UTF-8?B?' . base64_encode($this->subject) . '?=',
        ];

        if ($this->bcc !== '' && $this->bcc !== '0') {
            $this->emailHeaders[] = "Bcc: {$this->bcc}";
        }
    }

    /**
     * Constructs the message body for the email.
     * This method builds the email message body, handling both cases with and without attachments.
     * For emails with attachments, it creates a multipart/mixed message with nested multipart/alternative content.
     * For emails without attachments, it creates a simple multipart/alternative message.
     * The method sets up appropriate MIME boundaries, constructs the text and HTML parts of the email,
     * and includes any attachments if present.
     *
     * @throws RandomException If there's an issue generating random bytes for the MIME boundary.
     */
    protected function constructMessageBody(): void
    {
        try {
            if ($this->attachments !== []) {
                $mixedBoundary = '=_Mixed_' . bin2hex(random_bytes(16));
                $alternativeBoundary = $this->boundary;

                $this->emailHeaders[] = "Content-Type: multipart/mixed; boundary=\"{$mixedBoundary}\"";

                $this->messageBody = [
                    "--{$mixedBoundary}",
                    "Content-Type: multipart/alternative; boundary=\"{$alternativeBoundary}\"",
                    "",
                ];

                $this->constructAlternativePart($alternativeBoundary);
                $this->processEmailAttachments($mixedBoundary);
                $this->messageBody[] = "--{$mixedBoundary}--";
            } else {
                $this->emailHeaders[] = "Content-Type: multipart/alternative; boundary=\"{$this->boundary}\"";
                $this->constructAlternativePart($this->boundary);
            }
        } catch (Exception $e) {
            throw new RandomException('Error generating random bytes for MIME boundary', 500, $e);
        }
    }

    /**
     * Validates an email address.
     * This method checks if the provided email address is valid.
     *
     * @param string $email The email address to be validated.
     * @throws InvalidEmailAddressException If the email address is invalid.
     */
    private function validateEmail(string $email): void
    {
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailAddressException('Invalid email address: ' . $email, 406);
        }
    }

    /**
     * Validates an attachment file for email sending.
     * This method checks if the file exists, is within the allowed size limit,
     * and has an allowed MIME type.
     *
     * @param string $filePath The full path to the attachment file.
     * @throws InvalidArgumentException If the file doesn't exist, exceeds size limit,
     *                                  or has an unsupported MIME type.
     */
    private function validateAttachment(string $filePath): void
    {
        if (! file_exists($filePath)) {
            throw new InvalidAttachmentException('Attachment file does not exist: ' . $filePath, 406);
        }

        $fileSize = filesize($filePath);
        if ($fileSize === false || $fileSize > self::MAX_ATTACHMENT_SIZE) {
            throw new InvalidAttachmentException(
                'Attachment file size exceeds the maximum allowed size of 10 MB.',
                406
            );
        }

        $mimeType = mime_content_type($filePath);
        if ($mimeType === false || ! in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new InvalidAttachmentException(
                'Attachment file type is not allowed: ' . ($mimeType ?: 'Unknown'),
                406
            );
        }
    }

    /**
     * Validates that all required email data is set.
     * This method checks if the essential email components (sender email, recipient email,
     * subject, and body) are properly set before sending an email. It throws exceptions
     * for any missing required data.
     *
     * @throws MissingRequiredParameterException() If any of the required email fields (sender email, recipient email, subject, or body) are not set.
     */
    private function fullEmailDataValidation(): void
    {
        if ($this->senderEmail === '' || $this->senderEmail === '0') {
            throw new MissingRequiredParameterException('Sender email is not set.', 406);
        }

        if ($this->recipientEmail === '' || $this->recipientEmail === '0') {
            throw new MissingRequiredParameterException('Recipient email is not set.', 406);
        }

        if ($this->subject === '' || $this->subject === '0') {
            throw new MissingRequiredParameterException('Email subject is not set.', 406);
        }

        if (($this->bodyText === '' || $this->bodyText === '0') && ($this->bodyHtml === '' || $this->bodyHtml === '0')) {
            throw new MissingRequiredParameterException('Email body is not set.', 406);
        }
    }

    /**
     * Returns the formatted sender information for the email.
     * This method constructs the "From" field of the email, including both the sender's name
     * (if available) and email address. The sender's name is encoded to handle special characters.
     *
     * @return string The formatted sender string. If a sender name is set, it returns the format
     *                "Encoded Name <email@example.com>". Otherwise, it returns just the email address.
     */
    private function getFormattedSender(): string
    {
        if ($this->senderName !== '' && $this->senderName !== '0') {
            $encodedName = $this->encodeHeader($this->senderName);
            return "{$encodedName} <{$this->senderEmail}>";
        }

        return $this->senderEmail;
    }

    /**
     * Encodes a header value if it contains non-ASCII characters.
     * This function checks if the given header value contains any non-ASCII characters.
     * If it does, the value is encoded using Base64 encoding and formatted according
     * to the MIME encoded-word syntax for use in email headers.
     *
     * @param string $value The header value to be encoded.
     * @return string The encoded header value if it contains non-ASCII characters,
     *                or the original value if it only contains ASCII characters.
     */
    private function encodeHeader(string $value): string
    {
        if (preg_match('/[\x80-\xFF]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        return $value;
    }

    private function constructAlternativePart(string $boundary): void
    {
        $this->messageBody[] = "--{$boundary}";
        $this->messageBody[] = "Content-Type: text/plain; charset=UTF-8";
        $this->messageBody[] = "Content-Transfer-Encoding: quoted-printable";
        $this->messageBody[] = "";
        $this->messageBody[] = quoted_printable_encode($this->bodyText);

        $this->messageBody[] = "--{$boundary}";
        $this->messageBody[] = "Content-Type: text/html; charset=UTF-8";
        $this->messageBody[] = "Content-Transfer-Encoding: quoted-printable";
        $this->messageBody[] = "";
        $this->messageBody[] = quoted_printable_encode($this->bodyHtml);

        $this->messageBody[] = "--{$boundary}--";
    }

    private function processEmailAttachments(string $mixedBoundary): void
    {
        foreach ($this->attachments as $attachment) {
            $filename = basename($attachment);
            /** @var string $content */
            $content = file_get_contents($attachment);
            $mimeType = mime_content_type($attachment);

            $this->messageBody[] = "--{$mixedBoundary}";
            $this->messageBody[] = "Content-Type: {$mimeType}; name=\"{$filename}\"";
            $this->messageBody[] = "Content-Disposition: attachment; filename=\"{$filename}\"";
            $this->messageBody[] = "Content-Transfer-Encoding: base64";
            $this->messageBody[] = "";
            $this->messageBody[] = chunk_split(base64_encode($content));
        }
    }
}
