<?php

use Aws\Result;
use Aws\Ses\SesClient;
use CreativeCrafts\EmailService\Exceptions\EmailThrottleException;
use CreativeCrafts\EmailService\Exceptions\InvalidAttachmentException;
use CreativeCrafts\EmailService\Exceptions\InvalidEmailAddressException;
use CreativeCrafts\EmailService\Exceptions\TemplateEngineNotSetException;
use CreativeCrafts\EmailService\Interfaces\RateLimiterInterface;
use CreativeCrafts\EmailService\Interfaces\TemplateEngineInterface;
use CreativeCrafts\EmailService\Interfaces\TemplateInterface;
use CreativeCrafts\EmailService\Services\EmailService;
use GuzzleHttp\Promise\Promise;
use Psr\Log\LoggerInterface;

covers(EmailService::class);

beforeEach(
    function () {
        $this->sesClient = Mockery::mock(SesClient::class);
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->rateLimiter = Mockery::mock(RateLimiterInterface::class);
        $this->templateEngine = Mockery::mock(TemplateEngineInterface::class);

        $this->emailService = new EmailService(
            $this->sesClient,
            $this->logger,
            $this->rateLimiter,
            $this->templateEngine
        );
    }
);

afterEach(function () {
    Mockery::close();
});

it('constructs EmailService with all dependencies', function () {
    expect($this->emailService)->toBeInstanceOf(EmailService::class);
});

it('sets sender email correctly', function () {
    $email = 'sender@example.com';
    $result = $this->emailService->setSenderEmail($email);

    expect($result)->toBeInstanceOf(EmailService::class);
});

it('throws exception for invalid sender email', function () {
    $this->emailService->setSenderEmail('invalid-email');
})->throws(InvalidEmailAddressException::class);

it('sets sender name correctly', function () {
    $name = 'John Doe';
    $result = $this->emailService->setSenderName($name);

    expect($result)->toBeInstanceOf(EmailService::class);
});

it('sets recipient email correctly', function () {
    $email = 'recipient@example.com';
    $result = $this->emailService->setRecipientEmail($email);

    expect($result)->toBeInstanceOf(EmailService::class);
});

it('throws exception for invalid recipient email', function () {
    $this->emailService->setRecipientEmail('invalid-email');
})->throws(InvalidEmailAddressException::class);

it('sets subject correctly', function () {
    $subject = 'Test Subject';
    $result = $this->emailService->setSubject($subject);

    expect($result)->toBeInstanceOf(EmailService::class);
});

it('sets body text correctly', function () {
    $bodyText = 'This is a test email body';
    $result = $this->emailService->setBodyText($bodyText);

    expect($result)->toBeInstanceOf(EmailService::class);
});

it('sets body HTML correctly', function () {
    $bodyHtml = '<p>This is a test email body</p>';
    $result = $this->emailService->setBodyHtml($bodyHtml);

    expect($result)->toBeInstanceOf(EmailService::class);
});

it('sets email template correctly', function () {
    $templateName = 'test-template';
    $variables = ['key' => 'value'];

    $templateMock = Mockery::mock(TemplateInterface::class);
    $templateMock->shouldReceive('render')
        ->once()
        ->with($variables)
        ->andReturn('<p>Rendered template</p>');

    $this->templateEngine->shouldReceive('load')
        ->once()
        ->with($templateName)
        ->andReturn($templateMock);

    $result = $this->emailService->setEmailTemplate($templateName, $variables);

    expect($result)->toBeInstanceOf(EmailService::class);
});

it('throws exception when setting email template without template engine', function () {
    $emailService = new EmailService($this->sesClient, $this->logger);
    $emailService->setEmailTemplate('template');
})->throws(TemplateEngineNotSetException::class);

it('adds attachment correctly', function () {
    $filePath = sys_get_temp_dir() . '/test-attachment.pdf';

    // Create a simple PDF file
    $pdfContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000010 00000 n\n0000000053 00000 n\n0000000102 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n149\n%%EOF";
    file_put_contents($filePath, $pdfContent);

    $result = $this->emailService->addAttachment($filePath);

    expect($result)->toBeInstanceOf(EmailService::class);

    unlink($filePath);
});

it('throws exception for invalid attachment', function () {
    $filePath = sys_get_temp_dir() . '/test-attachment.txt';
    file_put_contents($filePath, 'Test content');
    $this->emailService->addAttachment($filePath);

    unlink($filePath);
})->throws(InvalidAttachmentException::class, 'Attachment file type is not allowed: text/plain');

it('throws exception for non-existent attachment', function () {
    $this->emailService->addAttachment('/non/existent/file.txt');
})->throws(InvalidAttachmentException::class, 'Attachment file does not exist: /non/existent/file.txt');

it('sends email successfully', function () {
    setupValidEmailData($this->emailService);

    $this->rateLimiter->shouldReceive('allow')
        ->once()
        ->andReturn(true);

    $this->sesClient->shouldReceive('sendRawEmail')
        ->once()
        ->andReturn(new Result(['MessageId' => 'test-message-id']));

    $this->logger->shouldReceive('info')
        ->once()
        ->with('Email sent successfully', ['messageId' => 'test-message-id']);

    $result = $this->emailService->sendEmail();

    expect($result)->toBeInstanceOf(Result::class)
        ->and($result->get('MessageId'))->toBe('test-message-id');
});

it('throws exception when rate limit is exceeded', function () {
    setupValidEmailData($this->emailService);
    $this->rateLimiter->shouldReceive('allow')
        ->once()
        ->andReturn(false);

    $this->emailService->sendEmail();
})->throws(EmailThrottleException::class, 'Email sending rate limit exceeded');

it('sends email asynchronously', function () {
    setupValidEmailData($this->emailService);

    $this->rateLimiter->shouldReceive('allow')
        ->once()
        ->andReturn(true);

    $promise = new Promise();
    $promise->resolve(['MessageId' => 'async-test-message-id']);

    $this->sesClient->shouldReceive('sendRawEmailAsync')
        ->once()
        ->andReturn($promise);

    $this->logger->shouldReceive('info')
        ->once()
        ->with('Email sent successfully', ['messageId' => 'async-test-message-id']);

    $result = $this->emailService->sendEmailAsync();

    expect($result)->toBeInstanceOf(Promise::class);
    $result->wait();
});

it('logs error when async email sending fails', function () {
    setupValidEmailData($this->emailService);

    $this->rateLimiter->shouldReceive('allow')
        ->once()
        ->andReturn(true);

    $promise = new Promise();
    $promise->reject(new Exception('Test error'));

    $this->sesClient->shouldReceive('sendRawEmailAsync')
        ->once()
        ->andReturn($promise);

    $this->logger->shouldReceive('error')
        ->once()
        ->with('Error sending email', ['error' => 'Test error']);

    $result = $this->emailService->sendEmailAsync();

    expect($result)->toBeInstanceOf(Promise::class);

    $exceptionThrown = false;
    try {
        $result->wait();
    } catch (Exception $e) {
        $exceptionThrown = true;
        expect($e->getMessage())->toBe('Test error');
    }

    expect($exceptionThrown)->toBeTrue();
});
