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

// Optionally, you can specify the file extension if your templates use a different extension. default is html
$templateExtension = 'phtml';
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
However, for more complex parts like the item list and conditional messages, you need to prepare these in the PHP code before passing them to the template. 
This approach keeps the template simple and focused on the email content structure, while the PHP code handles the data preparation and logic.

### Advanced Template Engine

The Advanced Template Engine allows you to work with more complex, PHP-based templates.

```php
use CreativeCrafts\EmailService\Services\Templates\Engines\AdvancedTemplateEngine;
// Optionally, you can specify the file extension if your templates use a different extension. default is html
$templateExtension = 'phtml';
$engine = new AdvancedTemplateEngine('/path/to/templates', $templateExtension);
$template = $engine->load('user_profile');
$renderedContent = $template->render(['user' => $userObject]);
```

This is ideal when you need to generate complex, data-driven emails. It's particularly useful for applications that send highly personalized or dynamic content, such as user reports or customized newsletters.

### Usage with EmailService

```php
use Aws\Ses\SesClient;
use CreativeCrafts\EmailService\Services\EmailService;
use CreativeCrafts\EmailService\Services\Templates\Engines\AdvancedTemplateEngine;

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

// Set up the AdvancedTemplateEngine
$templateEngine = new AdvancedTemplateEngine('/path/to/your/templates');

// Create the EmailService with the AdvancedTemplateEngine
$emailService = new EmailService($sesClient, null, null, $templateEngine);

// Prepare the email data
$emailData = [
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
];

// Send the email using a template
try {
    $emailService->setSenderEmail('sender@yourdomain.com')
        ->setRecipientEmail($emailData['user']['email'])
        ->setSubject('Your Order Confirmation')
        ->setEmailTemplate('order_confirmation', $emailData)
        ->sendEmail();

    echo "Email sent successfully!";
} catch (Exception $e) {
    echo "Error sending email: " . $e->getMessage();
}
```
Now, let's create an example of an advanced template that this code might use:

```php
// order_confirmation.html or order_confirmation.php or order_confirmation.phtml
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation</title>
</head>
<body>
    <h1>Thank you for your order, <?php echo htmlspecialchars($user['name']); ?>!</h1>
    
    <p>Your order (ID: <?php echo htmlspecialchars($order['id']); ?>) has been confirmed.</p>
    
    <h2>Order Details:</h2>
    <table>
        <tr>
            <th>Product</th>
            <th>Price</th>
        </tr>
        <?php foreach ($order['items'] as $item): ?>
        <tr>
            <td><?php echo htmlspecialchars($item['name']); ?></td>
            <td>$<?php echo number_format($item['price'], 2); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    
    <p><strong>Total: $<?php echo number_format($order['total'], 2); ?></strong></p>
    
    <?php if ($order['total'] > 100): ?>
    <p>As a valued customer spending over $100, you've earned free shipping on this order!</p>
    <?php endif; ?>
    
    <p>If you have any questions about your order, please don't hesitate to contact us.</p>
    
    <p>Best regards,<br>Your Company Name</p>
</body>
</html>
```

## Logging

Logging is crucial for tracking email operations, troubleshooting, and maintaining an audit trail.

```php
use CreativeCrafts\EmailService\Services\Logger\Logger;

$logger = new Logger('/path/to/email.log');
$logger->info('Email sent successfully', ['to' => 'recipient@example.com']);
```

Use logging to keep track of successful sends, failures, and other important events in your email operations. 
This can be invaluable for debugging and monitoring your application's email functionality.

### Usage with EmailService

```php
use Aws\Ses\SesClient;
use CreativeCrafts\EmailService\Services\EmailService;
use CreativeCrafts\EmailService\Services\Logger\Logger;

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

// Set up the Logger
$logger = new Logger('/path/to/your/email.log');

// Create the EmailService with the Logger
$emailService = new EmailService($sesClient, $logger);

// Prepare the email data
$senderEmail = 'sender@example.com';
$recipientEmail = 'recipient@example.com';
$subject = 'Test Email with Logging';
$bodyText = 'This is a test email sent with logging enabled.';

// Send the email
try {
    $result = $emailService->setSenderEmail($senderEmail)
        ->setRecipientEmail($recipientEmail)
        ->setSubject($subject)
        ->setBodyText($bodyText)
        ->sendEmail();

    // Log the successful email send
    $logger->info('Email sent successfully', [
        'messageId' => $result['MessageId'],
        'to' => $recipientEmail,
        'subject' => $subject
    ]);

    echo "Email sent successfully! Message ID: " . $result['MessageId'];
} catch (Exception $e) {
    // Log the error
    $logger->error('Failed to send email', [
        'error' => $e->getMessage(),
        'to' => $recipientEmail,
        'subject' => $subject
    ]);

    echo "Error sending email: " . $e->getMessage();
}

// Example of logging other email-related activities
$logger->info('Email queue processed', ['queueSize' => 10, 'processTime' => '2.5s']);
$logger->warning('Rate limit approaching', ['currentRate' => 95, 'limit' => 100]);
$logger->error('Failed to connect to email server', ['server' => 'smtp.example.com']);
```

## Rate Limiting

Rate limiting helps you control the volume of emails sent, ensuring you stay within Amazon SES limits and avoid overwhelming recipients.

### In-Memory Rate Limiter

Suitable for single-server setups or applications with low to moderate email volume.

```php
use Aws\Ses\SesClient;
use CreativeCrafts\EmailService\Services\EmailService;
use CreativeCrafts\EmailService\Services\RateLimiter\InMemoryRateLimiter;

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

// Set up the InMemoryRateLimiter
// This example sets a limit of 100 emails per hour
$rateLimiter = new InMemoryRateLimiter(100, 3600);

// Create the EmailService with the InMemoryRateLimiter
$emailService = new EmailService($sesClient, null, $rateLimiter);

// Prepare the email data
$senderEmail = 'sender@example.com';
$recipientEmail = 'recipient@example.com';
$subject = 'Test Email with Rate Limiting';
$bodyText = 'This is a test email sent with rate limiting enabled.';

// Function to send an email
function sendEmail($emailService, $senderEmail, $recipientEmail, $subject, $bodyText) {
    try {
        $result = $emailService->setSenderEmail($senderEmail)
            ->setRecipientEmail($recipientEmail)
            ->setSubject($subject)
            ->setBodyText($bodyText)
            ->sendEmail();

        echo "Email sent successfully! Message ID: " . $result['MessageId'] . "\n";
    } catch (Exception $e) {
        echo "Error sending email: " . $e->getMessage() . "\n";
    }
}

// Simulate sending multiple emails
for ($i = 0; $i < 120; $i++) {
    sendEmail($emailService, $senderEmail, $recipientEmail, $subject, $bodyText);
    
    // Sleep for a short time to simulate time passing between email sends
    usleep(100000); // 0.1 seconds
}
```
Use this for smaller applications or when you don't need to share rate limit data across multiple servers. Be aware that the limits reset if your application restarts.

### Redis Rate Limiter

Ideal for distributed systems or high-volume applications that require persistent and shared rate limiting.

```php
use Aws\Ses\SesClient;
use CreativeCrafts\EmailService\Services\EmailService;
use CreativeCrafts\EmailService\Services\RateLimiter\RedisRateLimiter;
use Redis;

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

// Set up Redis connection
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

// Set up the RedisRateLimiter
// This example sets a limit of 100 emails per hour
$rateLimiter = new RedisRateLimiter($redis, 'email_rate_limit', 100, 3600);

// Create the EmailService with the RedisRateLimiter
$emailService = new EmailService($sesClient, null, $rateLimiter);

// Prepare the email data
$senderEmail = 'sender@example.com';
$recipientEmail = 'recipient@example.com';
$subject = 'Test Email with Redis Rate Limiting';
$bodyText = 'This is a test email sent with Redis rate limiting enabled.';

// Function to send an email
function sendEmail($emailService, $senderEmail, $recipientEmail, $subject, $bodyText) {
    try {
        $result = $emailService->setSenderEmail($senderEmail)
            ->setRecipientEmail($recipientEmail)
            ->setSubject($subject)
            ->setBodyText($bodyText)
            ->sendEmail();

        echo "Email sent successfully! Message ID: " . $result['MessageId'] . "\n";
    } catch (Exception $e) {
        echo "Error sending email: " . $e->getMessage() . "\n";
    }
}

// Simulate sending multiple emails
for ($i = 0; $i < 120; $i++) {
    sendEmail($emailService, $senderEmail, $recipientEmail, $subject, $bodyText);
    
    // Sleep for a short time to simulate time passing between email sends
    usleep(100000); // 0.1 seconds
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
