# Changelog

All notable changes to `php-aws-send-email` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-11-23

- Bug fixes and performance improvements.
- Added feature to AdvancedTemplateEngine to handle partial template inside the main template.

## [0.9.0] - 2024-11-23

- Bug fixes and performance improvements.

## [0.8.0] - 2024-11-23

- Bug fixes and performance improvements.

## [0.7.0] - 2024-11-23

- Bug fixes and performance improvements.

## [0.6.0] - 2024-11-23

### Added
- feat(template-engines): allow customizable template file extensions
- Updated 'SimpleTemplateEngine'and 'AdvancedTemplateEngine' to support customizable template file extensions.
- Modified constructors to accept an optional parameter for the template file extension, defaulting to '.html'.
- Updated README to document the new feature and provide examples of usage.

## [0.5.0] - 2024-11-23
- Updated README.md with detailed usage instructions.

## [0.4.0] - 2024-11-22

### Added
- Initial release of the php-aws-send-email package.
- Core `EmailService` class for sending emails via Amazon SES.
- Support for both synchronous and asynchronous email sending.
- Email validation functionality to ensure valid sender and recipient addresses.
- HTML to plain text conversion for email bodies.
- Attachment support with size and type validation.
- Rate limiting interface to control email sending frequency.
- Template engine interface for rendering email templates.
- Comprehensive logging using PSR-3 LoggerInterface.

### Security
- Implemented input sanitization to prevent XSS attacks in email content.
- Added MIME type validation for email attachments.

## [0.3.0] - 2024-11-22

### Added
- Full documentation in README.md.

### Changed
- Finalized API design for `EmailService` class.
- Optimized performance for large attachments.

### Fixed
- Resolved issues with UTF-8 encoding in email headers.

## [0.2.0] - 2024-11-22

### Added
- Asynchronous email sending capability.
- Support for email templates.
- Ability to add multiple attachments.

### Changed
- Improved error handling and reporting.
- Enhanced logging with more detailed information.

### Fixed
- Bug in attachment MIME type detection.

## [0.1.0] - 2024-11-22

### Added
- Basic email sending functionality using Amazon SES.
- Support for plain text and HTML email bodies.
- Simple attachment handling.
- Basic input validation.