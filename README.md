# PHP AWS Send Email

[![Latest Version on Packagist](https://img.shields.io/packagist/v/creativecrafts/php-aws-send-email.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/php-aws-send-email)
[![Tests](https://img.shields.io/github/actions/workflow/status/creativecrafts/php-aws-send-email/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/creativecrafts/php-aws-send-email/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/creativecrafts/php-aws-send-email.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/php-aws-send-email)

A powerful and flexible PHP package for sending emails via Amazon SES (Simple Email Service) with advanced features including templating, logging, and rate limiting. This package is designed to simplify email operations while providing robust controls and customization options.

## Features

- Send emails using Amazon SES
- Template support (Simple and Advanced)
- Logging capabilities
- Rate limiting (In-Memory and Redis options)
- Easy to use and integrate

Our features are designed to cater to a wide range of email sending needs, from simple transactional emails to complex, templated marketing campaigns.

## Installation

You can install the package via composer:

```bash
composer require creativecrafts/php-aws-send-email
```

Ensure you have Composer installed on your system before running this command. This package requires PHP 8.2 or higher.

## Usage

### Basic Usage

The EmailService class is the core of this package, providing a simple interface to send emails via Amazon SES.

```php
use Aws\Ses\SesClient;
use CreativeCrafts\EmailService\Services\EmailService;

$sesClient = new SesClient([
    'version' => 'latest',
    'region'  => 'us-west-2',
    /*'credentials' => [
        'key'    => 'YOUR_AWS_ACCESS_KEY_ID',
        'secret' => 'YOUR_AWS_SECRET_ACCESS_KEY',
    ],*// Remove the 'credentials' array if you're using environment variables or IAM roles (Recommended)
]);

$emailService = new EmailService($sesClient);

$emailService->setSenderEmail('sender@example.com')
    ->setRecipientEmail('recipient@example.com')
    ->setSubject('Test Email')
    ->setBodyText('This is a test email.')
    ->sendEmail();
```
Consider using this for sending transactional emails, notifications, or any automated email communication. 
Remember to handle exceptions that might occur during the sending process.

### Using Optional Features

This package offers additional features to enhance your email sending capabilities. By leveraging a logger, rate limiter, and template engine, you can create a more robust and flexible email service.

```php
use Aws\Ses\SesClient;
use CreativeCrafts\EmailService\Services\EmailService;
use Psr\Log\LoggerInterface;
use CreativeCrafts\EmailService\Interfaces\RateLimiterInterface;
use CreativeCrafts\EmailService\Interfaces\TemplateEngineInterface;

$sesClient = new SesClient([/* configuration */]);
$logger = new YourLoggerImplementation();
$rateLimiter = new YourRateLimiterImplementation();
$templateEngine = new YourTemplateEngineImplementation();

$emailService = new EmailService($sesClient, $logger, $rateLimiter, $templateEngine);

$emailService->setSenderEmail('sender@example.com')
    ->setRecipientEmail('recipient@example.com')
    ->setSubject('Test Email')
    ->setEmailTemplate('welcome', ['name' => 'John'])
    ->sendEmail();
```

By incorporating these optional features, you can log email activities, control sending rates, and use templates for consistent and dynamic email content. 
This approach is particularly useful for applications that send a high volume of emails or require detailed tracking and control over the email sending process.

### Advanced Usage

The EmailService class provides advanced features to handle more complex email sending scenarios. Here are some examples of how you can leverage these capabilities:

#### Adding Attachments

You can easily add file attachments to your emails using the `addAttachment method`. This is useful for sending documents, images, or any other files along with your email content.

```php
$emailService->addAttachment('/path/to/file.pdf');
```

This feature is particularly helpful when you need to send invoices, reports, or supplementary materials along with your emails. 
You can add multiple attachments by calling this method multiple times.

#### Asynchronous Sending

For applications that need to handle high volumes of emails without blocking the main execution thread, the sendEmailAsync method provides an asynchronous approach to email sending.

```php
$promise = $emailService->sendEmailAsync();
$promise->then(
    function ($result) {
        echo "Email sent! Message ID: " . $result['MessageId'];
    },
    function ($error) {
        echo "An error occurred: " . $error->getMessage();
    }
);
```

Asynchronous sending is beneficial in scenarios where you're sending multiple emails simultaneously or when you want to improve the performance of your application by not waiting for the email to be sent before continuing execution. 
This method returns a promise, allowing you to handle the success or failure of the email sending operation in a non-blocking manner.

## Using Templates

Templates allow you to create reusable email content, making it easier to maintain consistent messaging across your application. 
This package supports two types of templates: Simple and Advanced.

### Simple Templates

Simple templates are best for straightforward, text-based emails with basic variable substitution.

```php
use CreativeCrafts\EmailService\Services\Templates\SimpleTemplate;

$template = new SimpleTemplate('Hello, {name}!');
$renderedContent = $template->render(['name' => 'John']);
```

Use simple templates for welcome emails, password reset notifications, or any email where the content structure remains largely the same with only a few changing variables.

### Advanced Template

Advanced templates offer more flexibility, allowing you to use PHP in your templates for complex logic and data manipulation.

```php
use CreativeCrafts\EmailService\Services\Templates\AdvancedTemplate;

$template = new AdvancedTemplate('/path/to/template.php');
$renderedContent = $template->render(['name' => 'John', 'age' => 30]);
```

Advanced templates are ideal for emails that require conditional content, loops, or complex data presentation. 
They're great for personalized newsletters, detailed reports, or any email where the content structure might vary significantly based on the data.

## Template Engines

Template engines provide a way to manage and render multiple templates efficiently. 
This package includes two template engines: SimpleTemplateEngine and AdvancedTemplateEngine.

### Simple Template Engine

The Simple Template Engine is perfect for managing multiple simple templates.

 ```php
use CreativeCrafts\EmailService\Services\Templates\Engines\SimpleTemplateEngine;

$engine = new SimpleTemplateEngine('/path/to/templates');
$template = $engine->load('welcome');
$renderedContent = $template->render(['name' => 'John']);
```
Use this when you have multiple simple email templates and want to keep them organized in separate files. 
It's great for managing a suite of transactional email templates.

### Advanced Template Engine

The Advanced Template Engine allows you to work with more complex, PHP-based templates.

```php
use CreativeCrafts\EmailService\Services\Templates\Engines\AdvancedTemplateEngine;

$engine = new AdvancedTemplateEngine('/path/to/templates');
$template = $engine->load('user_profile');
$renderedContent = $template->render(['user' => $userObject]);
```

This is ideal when you need to generate complex, data-driven emails. It's particularly useful for applications that send highly personalized or dynamic content, such as user reports or customized newsletters.

## Logging

Logging is crucial for tracking email operations, troubleshooting, and maintaining an audit trail.

```php
use CreativeCrafts\EmailService\Services\Logger\Logger;

$logger = new Logger('/path/to/email.log');
$logger->info('Email sent successfully', ['to' => 'recipient@example.com']);
```

Use logging to keep track of successful sends, failures, and other important events in your email operations. 
This can be invaluable for debugging and monitoring your application's email functionality.

## Rate Limiting

Rate limiting helps you control the volume of emails sent, ensuring you stay within Amazon SES limits and avoid overwhelming recipients.

### In-Memory Rate Limiter

Suitable for single-server setups or applications with low to moderate email volume.

```php
use CreativeCrafts\EmailService\Services\RateLimiter\InMemoryRateLimiter;

$rateLimiter = new InMemoryRateLimiter(100, 3600); // 100 emails per hour
```

Use this for smaller applications or when you don't need to share rate limit data across multiple servers. Be aware that the limits reset if your application restarts.

### Redis Rate Limiter

Ideal for distributed systems or high-volume applications that require persistent and shared rate limiting.

```php
use CreativeCrafts\EmailService\Services\RateLimiter\RedisRateLimiter;

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
$rateLimiter = new RedisRateLimiter($redis, 'email_rate_limit', 100, 3600); // 100 emails per hour
```

Use this when you have multiple application servers and need to enforce a global rate limit. 
It's also useful for maintaining rate limits across application restarts.

## Configuration
Proper configuration is key to getting the most out of this package. Here's what to consider for each component:

    - EmailService: Ensure your SES client is properly configured with your AWS credentials. Choose appropriate logger and rate limiter implementations based on your needs.
    - Logger: Choose a log file path that's writable by your application and easily accessible for monitoring.
    - InMemoryRateLimiter: Set limits that align with your SES account limits and expected email volumes.
    - RedisRateLimiter: Ensure your Redis connection is stable and consider using a dedicated Redis instance for rate limiting in high-volume scenarios.
    - SimpleTemplateEngine and AdvancedTemplateEngine: Organize your templates in a logical directory structure for easy management.


## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](https://github.com/spatie/.github/blob/main/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Godspower](https://github.com/Prince)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
