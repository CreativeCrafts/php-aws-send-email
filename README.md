# PHP AWS Send Email

[![Latest Version on Packagist](https://img.shields.io/packagist/v/creativecrafts/php-aws-send-email.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/php-aws-send-email)
[![Tests](https://img.shields.io/github/actions/workflow/status/creativecrafts/php-aws-send-email/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/creativecrafts/php-aws-send-email/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/creativecrafts/php-aws-send-email.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/php-aws-send-email)

A powerful and flexible PHP package for sending emails via Amazon SES (Simple Email Service) with advanced features including templating, attachments, logging, and rate limiting. This package is designed to simplify email operations while providing robust controls and customization options.

## Features

- **Send Emails Using Amazon SES:** Leverage the scalability and reliability of AWS SES to send your emails.
- **Advanced Templating:** Utilize both Simple and Advanced template engines for dynamic and reusable email content.
- **Attachments Support:** Easily attach multiple files (PDFs, images, etc.) to your emails.
- **Logging Capabilities:** Track email operations with integrated logging for monitoring and troubleshooting.
- **Rate Limiting:** Control email sending frequency with In-Memory and Redis-based rate limiters.
- **Asynchronous Sending:** Improve application performance by sending emails asynchronously.
- **Partial Rendering:** Include shared components across multiple templates without compromising security.
- **Secure Variable Handling:** Avoid security vulnerabilities.

These features cater to a wide range of email sending needs, from simple transactional emails to complex, templated marketing campaigns with attachments.

## Installation

You can install the package via Composer:

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

// Initialize the SES client
$sesClient = new SesClient([
    'version' => 'latest',
    'region'  => 'us-west-2',
    // Uncomment and fill in your AWS credentials if not using environment variables or IAM roles (Not Recommended)
    /*
    'credentials' => [
        'key'    => 'YOUR_AWS_ACCESS_KEY_ID',
        'secret' => 'YOUR_AWS_SECRET_ACCESS_KEY',
    ],
    */
]);

// Initialize the EmailService without optional features
$emailService = new EmailService($sesClient);

// Send a simple email
try {
    $emailService->setSenderEmail('sender@example.com')
        ->setRecipientEmail('recipient@example.com')
        ->setSubject('Test Email')
        ->setBodyText('This is a test email.')
        ->sendEmail();

    echo "Email sent successfully!";
} catch (Exception $e) {
    echo "Error sending email: " . $e->getMessage();
}
```
Use this for sending transactional emails, notifications, or any automated email communication. 
Remember to handle exceptions that might occur during the sending process.

### Using Optional Features

This package offers additional features to enhance your email sending capabilities. By leveraging a logger, rate limiter, and template engine, you can create a more robust and flexible email service.

```php
use Aws\Ses\SesClient;
use CreativeCrafts\EmailService\Services\EmailService;
use Psr\Log\LoggerInterface;
use CreativeCrafts\EmailService\Interfaces\RateLimiterInterface;
use CreativeCrafts\EmailService\Interfaces\TemplateEngineInterface;

// Initialize the SES client
$sesClient = new SesClient([
    'version' => 'latest',
    'region'  => 'us-west-2',
    // Uncomment and fill in your AWS credentials if not using environment variables or IAM roles (Not Recommended)
    /*
    'credentials' => [
        'key'    => 'YOUR_AWS_ACCESS_KEY_ID',
        'secret' => 'YOUR_AWS_SECRET_ACCESS_KEY',
    ],
    */
]);

// Initialize Logger, RateLimiter, and TemplateEngine (implement these interfaces as needed)
$logger = new YourLoggerImplementation();
$rateLimiter = new YourRateLimiterImplementation();
$templateEngine = new YourTemplateEngineImplementation();

// Create the EmailService with optional features
$emailService = new EmailService($sesClient, $logger, $rateLimiter, $templateEngine);

// Send a templated email
try {
    $emailService->setSenderEmail('sender@example.com')
        ->setRecipientEmail('recipient@example.com')
        ->setSenderName('Sender Name')
        ->setSubject('Welcome to Our Service')
        ->setEmailTemplate('welcome', ['name' => 'John Doe'])
        ->sendEmail();

    echo "Templated email sent successfully!";
} catch (Exception $e) {
    echo "Error sending templated email: " . $e->getMessage();
}
```
By incorporating these optional features, you can log email activities, control sending rates, and use templates for consistent and dynamic email content. 
This approach is particularly useful for applications that send a high volume of emails or require detailed tracking and control over the email sending process.

### Advanced Usage

The EmailService class provides advanced features to handle more complex email sending scenarios. Here are some examples of how you can leverage these capabilities:

#### Sending Emails with Attachments

You can easily add file attachments to your emails using the `addAttachment method`. This is useful for sending documents, images, or any other files along with your email content.
To send emails with multiple attachments seamlessly, follow these steps:
1. Initialize the EmailService with Optional Features:

```php
use Aws\Ses\SesClient;
use CreativeCrafts\EmailService\Services\EmailService;
use CreativeCrafts\EmailService\Services\Templates\Engines\AdvancedTemplateEngine;
use CreativeCrafts\EmailService\Services\Logger\Logger;

// Initialize the SES client
$sesClient = new SesClient([
    'version' => 'latest',
    'region'  => 'us-west-2',
]);

// Initialize the Logger
$logger = new Logger('/path/to/your/email.log');

// Initialize the AdvancedTemplateEngine with global variables
$templateEngine = new AdvancedTemplateEngine(
    '/path/to/templates',
    '/path/to/partials',
    'phtml',
    [
        'baseLight' => '#ed8b00',
        'baseMid'   => '#ed8b00',
        'baseDark'  => '#ed8b00',
        'greyLight' => '#dedede',
        'greyMid'   => '#bcbcbc',
        'greyDark'  => '#555555',
        'white'     => '#ffffff',
    ]
);

// Create the EmailService with the Logger and TemplateEngine
$emailService = new EmailService($sesClient, $logger, null, $templateEngine);
```
2. Prepare the Email Data and Add Attachments:
    
```php 
// Prepare email data with variables for the template
$emailData = [
    'etemplateheader' => 'Hej Jane Doe,', // Example header
    'etemplate' => "This is the sample content of the email template",
    'companydata' => [
        'name' => 'Your Company',
        'address' => '1234 Street Name',
        'areacode' => '12345',
        'city' => 'CityName',
        'country' => 'CountryName',
    ],
    'testid' => '136277',
];

// Add attachments
$emailService->addAttachment('/path/to/invoice.pdf')
    ->addAttachment('/path/to/image.jpg');
```
3. Send the Email:

```php
<?php

try {
    $emailService->setSenderEmail('sender@yourdomain.com')
        ->setRecipientEmail('jane.doe@example.com')
        ->setSenderName('Your Company')
        ->setSubject('Order confirmation')
        ->setEmailTemplate('send-test', $emailData)
        ->sendEmail();

    echo "Email with attachments sent successfully!";
} catch (Exception $e) {
    echo "Error sending email with attachments: " . $e->getMessage();
}
```
This feature is particularly helpful when you need to send invoices, reports, or supplementary materials along with your emails. 
You can add multiple attachments by calling this method multiple times.

#### Asynchronous Sending

For applications that need to handle high volumes of emails without blocking the main execution thread, the sendEmailAsync method provides an asynchronous approach to email sending.

```php
<?php

// Send the email asynchronously
$promise = $emailService->sendEmailAsync();

$promise->then(
    function ($result) {
        echo "Email sent! Message ID: " . $result['MessageId'];
    },
    function ($error) {
        echo "An error occurred: " . $error->getMessage();
    }
);

// Continue with other tasks while the email is being sent
echo "Email is being sent asynchronously.";
```
Asynchronous sending is beneficial in scenarios where you're sending multiple emails simultaneously or when you want to improve the performance of your application by not waiting for the email to be sent before continuing execution. 
This method returns a promise, allowing you to handle the success or failure of the email sending operation in a non-blocking manner.

### Using Templates with Attachments
Combine templating and attachments to send personalized emails with supplementary files.

```php
// Prepare email data with variables for the template
$emailData = [
    'etemplateheader' => 'Hej Jane Doe,', // Example header
    'etemplate' => "This is the sample content of the email template",
    'companydata' => [
        'name' => 'Your Company',
        'address' => '1234 Street Name',
        'areacode' => '12345',
        'city' => 'CityName',
        'country' => 'CountryName',
    ],
    'testid' => '136277',
];

// Add attachments
$emailService->addAttachment('/path/to/invoice.pdf')
    ->addAttachment('/path/to/image.jpg');

// Send the templated email with attachments
try {
    $emailService->setSenderEmail('sender@yourdomain.com')
        ->setRecipientEmail('jane.doe@example.com')
        ->setSenderName('Your Company')
        ->setSubject('Order confirmation')
        ->setEmailTemplate('send-test', $emailData)
        ->sendEmail();

    echo "Email with attachments sent successfully!";
} catch (Exception $e) {
    echo "Error sending email with attachments: " . $e->getMessage();
}
```

## Using Templates
Templates allow you to create reusable email content, making it easier to maintain consistent messaging across your application. 
This package supports two types of templates: ***Simple*** and ***Advanced***.

### Simple Templates

Simple templates are best for straightforward, text-based emails with basic variable substitution.

```php
use CreativeCrafts\EmailService\Services\Templates\Engines\SimpleTemplateEngine;

// Initialize the SimpleTemplateEngine
$templateEngine = new SimpleTemplateEngine('/path/to/templates');

// Load a simple template
$template = $templateEngine->load('welcome');

// Render the template with variables
$renderedContent = $template->render(['name' => 'John Doe']);
```
### Example Simple Template (welcome.phtml):

```php
Hello {name},

Welcome to our service! We're glad to have you on board.

Best regards,
Your Company Name
```
Use simple templates for welcome emails, password reset notifications, or any email where the content structure remains largely the same with only a few changing variables.

### Advanced Template

Advanced templates offer more flexibility, allowing you to use PHP in your templates for complex logic and data manipulation.

```php
<?php

use CreativeCrafts\EmailService\Services\Templates\Engines\AdvancedTemplateEngine;

// Initialize the AdvancedTemplateEngine with global variables
$templateEngine = new AdvancedTemplateEngine(
    '/path/to/templates',
    '/path/to/partials',
    'phtml',
    [
        'baseLight' => '#ed8b00',
        'baseMid'   => '#ed8b00',
        'baseDark'  => '#ed8b00',
        'greyLight' => '#dedede',
        'greyMid'   => '#bcbcbc',
        'greyDark'  => '#555555',
        'white'     => '#ffffff',
    ]
);

// Load an advanced template
$template = $templateEngine->load('order_confirmation');

// Render the template with variables
$renderedContent = $template->render([
    'user' => [
        'name' => 'John Doe',
        'email' => 'john.doe@example.com',
    ],
    'order' => [
        'id' => '12345',
        'items' => [
            ['name' => 'Product A', 'price' => 19.99],
            ['name' => 'Product B', 'price' => 29.99],
        ],
        'total' => 49.98,
    ],
]);
```
### Example Advanced Template (order_confirmation.phtml):

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation</title>
    <style>
        table, h1, h2, p { font-family: Helvetica, Arial, Sans-Serif; }
        p { margin-top: 3px; margin-bottom: 6px; }
        a:link { color: <?= $this->baseLight; ?>; }
        a:visited { color: <?= $this->baseDark; ?>; }
        a:hover { color: <?= $this->baseMid; ?>; }
    </style>
</head>
<body>
    <table width="599" cellpadding="10" cellspacing="0" style="margin:0 auto;">
        <tr>
            <td colspan="3" style="background-color: <?= $this->white; ?>; padding-left: 20px;">
                <h1 style="padding:0; margin:0; color: <?= $this->baseLight; ?>; font-size:24px;">Jobmatch.</h1>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="padding:10px; border:none;">
                <div style="background-color: <?= $this->white; ?>; border:none; padding:20px; font-size:14px; line-height:1.5em; color: <?= $this->greyDark; ?>;">
                    Hello <?= htmlspecialchars($this->user['name']); ?>,

                    <p>Thank you for your order (ID: <?= htmlspecialchars($this->order['id']); ?>)!</p>

                    <h2>Order Details:</h2>
                    <table>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                        </tr>
                        <?php foreach ($this->order['items'] as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['name']); ?></td>
                            <td>$<?= number_format($item['price'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>

                    <p><strong>Total: $<?= number_format($this->order['total'], 2); ?></strong></p>

                    <?php if ($this->order['total'] > 100): ?>
                        <p>As a valued customer spending over $100, you've earned free shipping on this order!</p>
                    <?php endif; ?>

                    <p>If you have any questions about your order, please don't hesitate to contact us.</p>

                    <p>Best regards,<br>Your Company Name</p>
                </div>
            </td>
        </tr>
        <tr>
            <td valign="top" align="left" colspan="3" style="background-color: <?= $this->white; ?>; font-size:11px; border:none;">
                <p><b><?= htmlspecialchars($this->company['name']); ?></b><br /></p>
                <p><?= htmlspecialchars($this->company['address']); ?><br />
                <?= htmlspecialchars($this->company['areacode']); ?> <?= htmlspecialchars($this->company['city']); ?><br />
                <?= htmlspecialchars($this->company['country']); ?><br /></p>
            </td>
        </tr>
    </table>
</body>
</html>
```
Advanced templates are ideal for emails that require conditional content, loops, or complex data presentation. 
They're great for personalized newsletters, detailed reports, or any email where the content structure might vary significantly based on the data.

## Template Engines

Template engines provide a way to manage and render multiple templates efficiently. 
This package includes two template engines: SimpleTemplateEngine and AdvancedTemplateEngine.

### Advanced Template Engine

The `AdvancedTemplateEngine` allows you to:

- Use PHP code directly in your templates for dynamic content.
- Include partial templates for shared components.
- Access variables using object-like syntax ($this->variableName).
- Implement complex logic within your templates safely without using extract().

### Using Partial Rendering and Shared Variables
Shared variables (like color codes) are defined once and made available across all templates and partials. This ensures consistency and reduces redundancy.

***Shared Variables*** (`snipplet-email-variables.php`):

```php
return [
    'baseLight' => '#ed8b00',
    'baseMid'   => '#ed8b00',
    'baseDark'  => '#ed8b00',
    'greyLight' => '#dedede',
    'greyMid'   => '#bcbcbc',
    'greyDark'  => '#555555',
    'white'     => '#ffffff',
];
```
### Including Partial Templates:
```php
<?php
// send-test.phtml

<?= $this->partial('snipplet-email-header'); ?>

<div style="background-color:#ffffff; border:none; padding:20px; margin-top:10px; font-size:14px; line-height:1.5em; color:#555555;">
    <?= nl2br($this->emailTemplateHeader); ?>
</div>

<div style="background-color:#ffffff; border:none; padding:20px; margin-top:10px; font-size:14px; line-height:1.5em; color:#555555;">
    <?= nl2br($this->emailTemplate); ?>
</div>

<?= $this->partial('snipplet-email-footer', ['company' => $this->companyInfo]); ?>

<p><small style="color: #fff; display: none;">u=<?= htmlspecialchars($this->name); ?></small></p>
```
### Partial Template (snipplet-email-header.phtml):

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Header</title>
    <style>
        table, h1, h2, p { font-family: Helvetica, Arial, Sans-Serif; }
        p { margin-top: 3px; margin-bottom: 6px; }
        a:link { color: <?= $this->baseLight; ?>; }
        a:visited { color: <?= $this->baseDark; ?>; }
        a:hover { color: <?= $this->baseMid; ?>; }
    </style>
</head>
<body>
    <table width="599" cellpadding="10" cellspacing="0" style="margin:0 auto;">
        <tr>
            <td style="padding:0;" width="275"></td>
            <td style="padding:0;" width="50"></td>
            <td style="padding:0;" width="275"></td>
        </tr>
        <tr>
            <td colspan="3" style="background-color: <?= $this->white; ?>; padding-left: 20px;">
                <h1 style="padding:0; margin:0; color: <?= $this->baseLight; ?>; font-size:24px;">CreativeCrafts Solution</h1>
            </td>
        </tr>
        <tr>
            <td colspan="3" style="padding:10px; border:none;">
                <div style="background-color: <?= $this->white; ?>; border:none; padding:20px; font-size:14px; line-height:1.5em; color: <?= $this->greyDark; ?>;">
```
This setup ensures that all templates and partials have access to the shared color variables without the need for including external files, enhancing security and maintainability.

### Simple Template Engine

The Simple Template Engine is perfect for managing multiple simple templates.

 ```php
use CreativeCrafts\EmailService\Services\Templates\Engines\SimpleTemplateEngine;

// Optionally, you can specify the file extension if your templates use a different extension. default is .html
$templateExtension = '.phtml';
$engine = new SimpleTemplateEngine('/path/to/templates', $templateExtension);
$template = $engine->load('welcome');
$renderedContent = $template->render(['name' => 'John']);
```
Use this when you have multiple simple email templates and want to keep them organized in separate files. 
It's great for managing a suite of transactional email templates.

### Usage with EmailService

```php
use Aws\Ses\SesClient;
use CreativeCrafts\EmailService\Services\EmailService;
use CreativeCrafts\EmailService\Services\Templates\Engines\SimpleTemplateEngine;

// Set up the SES client
$sesClient = new SesClient([
    'version' => 'latest',
    'region'  => 'us-west-2',
    // Uncomment and fill in your AWS credentials if not using environment variables or IAM roles
    /* 'credentials' => [
        'key'    => 'YOUR_AWS_ACCESS_KEY_ID',
        'secret' => 'YOUR_AWS_SECRET_ACCESS_KEY',
    ],*/
]);

// Set up the SimpleTemplateEngine
$templateEngine = new SimpleTemplateEngine('/path/to/your/templates');

// Create the EmailService with the SimpleTemplateEngine
$emailService = new EmailService($sesClient, null, null, $templateEngine);

// Prepare the email data
$emailData = [
    'name' => 'John Doe',
    'order_id' => '12345',
    'total' => 49.98,
    'items' => [
        ['name' => 'Product A', 'price' => 19.99],
        ['name' => 'Product B', 'price' => 29.99],
    ],
];

/ Prepare the item list
$itemList = '';
foreach ($emailData['items'] as $item) {
    $itemList .= "- {$item['name']}: $" . number_format($item['price'], 2) . "\n";
}

// Prepare the free shipping message
$freeShippingMessage = $emailData['total'] > 100 
    ? "As a valued customer spending over $100, you've earned free shipping on this order!"
    : "";

// Prepare the final email data
$finalEmailData = [
    'name' => $emailData['name'],
    'order_id' => $emailData['order_id'],
    'total' => number_format($emailData['total'], 2),
    'item_list' => $itemList,
    'free_shipping_message' => $freeShippingMessage,
];

// Send the email using a template
try {
    $emailService->setSenderEmail('sender@yourdomain.com')
        ->setRecipientEmail('john.doe@example.com')
        ->setSubject('Your Order Confirmation')
        ->setEmailTemplate('order_confirmation', $finalEmailData)
        ->sendEmail();

    echo "Email sent successfully!";
} catch (Exception $e) {
    echo "Error sending email: " . $e->getMessage();
}
```
Now, let's create an example of a simple template that this code might use:

```php
// order_confirmation.html or order_confirmation.php or order_confirmation.phtml
Hello {name},

Thank you for your order! Your order (ID: {order_id}) has been confirmed.

Order Details:
{item_list}

Total: ${total}

{free_shipping_message}

If you have any questions about your order, please don't hesitate to contact us.

Best regards,
Your Company Name
```
The SimpleTemplateEngine will replace the placeholders in curly braces with the corresponding values from the $emailData array. 

## Logging

Logging is crucial for tracking email operations, troubleshooting, and maintaining an audit trail.

### Example Logger Implementation:

```php
namespace CreativeCrafts\EmailService\Services\Logger;

use Psr\Log\AbstractLogger;

class Logger extends AbstractLogger
{
    private string $logFile;

    /**
     * Constructor.
     *
     * @param string $logFile The path to the log file.
     */
    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string|\Stringable  $message
     * @param array $context
     *
     * @return void
     */
    public function log($level, $message, array $context = []): void
    {
        $date = date('Y-m-d H:i:s');
        $contextString = json_encode($context);
        $logEntry = "[{$date}] {$level}: {$message} {$contextString}\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
}
```

### Using the Logger with EmailService:

```php
use Aws\Ses\SesClient;
use CreativeCrafts\EmailService\Services\EmailService;
use CreativeCrafts\EmailService\Services\Logger\Logger;

// Initialize the SES client
$sesClient = new SesClient([
    'version' => 'latest',
    'region'  => 'us-west-2',
]);

// Initialize the Logger
$logger = new Logger('/path/to/your/email.log');

// Create the EmailService with the Logger
$emailService = new EmailService($sesClient, $logger);

// Prepare and send the email
try {
    $emailService->setSenderEmail('sender@yourdomain.com')
        ->setRecipientEmail('recipient@example.com')
        ->setSenderName('Your Company')
        ->setSubject('Test Email with Logging')
        ->setBodyText('This is a test email sent with logging enabled.')
        ->sendEmail();

    // Log additional activities if needed
    $logger->info('Email sent successfully', [
        'messageId' => '1234567890',
        'to' => 'recipient@example.com',
        'subject' => 'Test Email with Logging'
    ]);

    echo "Email sent successfully!";
} catch (Exception $e) {
    // Log the error
    $logger->error('Failed to send email', [
        'error' => $e->getMessage(),
        'to' => 'recipient@example.com',
        'subject' => 'Test Email with Logging'
    ]);

    echo "Error sending email: " . $e->getMessage();
}

// Example of logging other email-related activities
$logger->info('Email queue processed', ['queueSize' => 10, 'processTime' => '2.5s']);
$logger->warning('Rate limit approaching', ['currentRate' => 95, 'limit' => 100]);
$logger->error('Failed to connect to email server', ['server' => 'smtp.example.com']);
```
Use logging to keep track of successful sends, failures, and other important events in your email operations. 
This can be invaluable for debugging and monitoring your application's email functionality.

## Rate Limiting

Rate limiting helps you control the volume of emails sent, ensuring you stay within Amazon SES limits and avoid overwhelming recipients.

### In-Memory Rate Limiter

Suitable for single-server setups or applications with low to moderate email volume.

```php
use Aws\Ses\SesClient;
use CreativeCrafts\EmailService\Services\EmailService;
use CreativeCrafts\EmailService\Services\RateLimiter\InMemoryRateLimiter;

// Initialize the SES client
$sesClient = new SesClient([
    'version' => 'latest',
    'region'  => 'us-west-2',
]);

// Initialize the InMemoryRateLimiter
// This example sets a limit of 100 emails per hour
$rateLimiter = new InMemoryRateLimiter(100, 3600);

// Create the EmailService with the InMemoryRateLimiter
$emailService = new EmailService($sesClient, null, $rateLimiter);

// Prepare and send the email
try {
    $emailService->setSenderEmail('sender@example.com')
        ->setRecipientEmail('recipient@example.com')
        ->setSenderName('Sender Name')
        ->setSubject('Test Email with Rate Limiting')
        ->setBodyText('This is a test email sent with rate limiting enabled.')
        ->sendEmail();

    echo "Email sent successfully!";
} catch (Exception $e) {
    echo "Error sending email: " . $e->getMessage();
}
```
Use this for smaller applications or when you don't need to share rate limit data across multiple servers. Be aware that the limits reset if your application restarts.

### Redis Rate Limiter

Ideal for distributed systems or high-volume applications that require persistent and shared rate limiting.

```php
<?php

use Aws\Ses\SesClient;
use CreativeCrafts\EmailService\Services\EmailService;
use CreativeCrafts\EmailService\Services\RateLimiter\RedisRateLimiter;
use Redis;

// Initialize the SES client
$sesClient = new SesClient([
    'version' => 'latest',
    'region'  => 'us-west-2',
]);

// Initialize Redis connection
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

// Initialize the RedisRateLimiter
// This example sets a limit of 100 emails per hour
$rateLimiter = new RedisRateLimiter($redis, 'email_rate_limit', 100, 3600);

// Create the EmailService with the RedisRateLimiter
$emailService = new EmailService($sesClient, null, $rateLimiter);

// Prepare and send the email
try {
    $emailService->setSenderEmail('sender@example.com')
        ->setRecipientEmail('recipient@example.com')
        ->setSenderName('Sender Name')
        ->setSubject('Test Email with Redis Rate Limiting')
        ->setBodyText('This is a test email sent with Redis rate limiting enabled.')
        ->sendEmail();

    echo "Email sent successfully!";
} catch (Exception $e) {
    echo "Error sending email: " . $e->getMessage();
}
```
The RedisRateLimiter offers several advantages over the InMemoryRateLimiter:
1. Persistence: The rate limit data is stored in Redis, so it persists even if your application restarts.
2. Distributed: If you have multiple application servers, they can all share the same rate limit by connecting to the same Redis instance.
3. Scalability: Redis is designed to handle high-concurrency scenarios, making it suitable for applications with high email volumes.

When using the RedisRateLimiter, consider the following:

    - Ensure your Redis server is properly configured and secured.
    - Monitor your Redis server's performance, especially in high-volume scenarios.
    - Consider using a dedicated Redis instance for rate limiting to isolate it from other Redis-based operations in your application.
    - Implement proper error handling for Redis connection issues.

This setup allows you to maintain consistent rate limiting across multiple application instances or server restarts, making it ideal for larger, distributed applications or those with high email volumes.

## Configuration
Proper configuration is key to getting the most out of this package. Here's what to consider for each component:

- EmailService:
  - SES Client: Ensure your SES client is properly configured with your AWS credentials and region.
  - Logger: Choose a log file path that's writable by your application and easily accessible for monitoring.
  - Rate Limiter: Select either InMemoryRateLimiter or RedisRateLimiter based on your application's needs and infrastructure.
  - Template Engine: Organize your templates in a logical directory structure and pass shared variables appropriately.
  
- Logger:
  - Ensure the log file path is secure and not publicly accessible.
  - Use appropriate log levels (info, warning, error) to categorize log messages effectively.
  
- Rate Limiter:
  - InMemoryRateLimiter: Set limits that align with your SES account limits and expected email volumes.
  - RedisRateLimiter: Ensure your Redis connection is stable and consider using a dedicated Redis instance for rate limiting in high-volume scenarios.

- Template Engines:
  - SimpleTemplateEngine: Organize your simple templates in a dedicated directory for easy management.
  - AdvancedTemplateEngine: Utilize partials and shared variables to maintain consistency across complex templates.

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

- [Godspower Oduose](https://github.com/rockblings)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
