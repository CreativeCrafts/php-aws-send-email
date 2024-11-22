<?php

namespace CreativeCrafts\EmailService\Services\Logger;

use CreativeCrafts\EmailService\DTO\LogEntry;
use CreativeCrafts\EmailService\DTO\LogLevel;
use DateTimeImmutable;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Stringable;
use ValueError;

/**
 * Logger class implementing PSR-3 LoggerInterface.
 */
class Logger implements LoggerInterface
{
    private string $logFile;

    /**
     * Constructor for the Logger class.
     *
     * @param string $logFile The path to the log file where messages will be written.
     */
    public function __construct(string $logFile)
    {
        $this->logFile = $logFile;
    }

    /**
     * Logs an emergency message.
     *
     * @param string|Stringable $message The log message.
     * @param array $context Additional context data for the log entry.
     */
    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Logs a message with a specified log level.
     *
     * @param mixed $level The log level (can be a LogLevel enum, string, or int).
     * @param string|Stringable $message The log message.
     * @param array $context Additional context data for the log entry.
     * @throws InvalidArgumentException If an invalid log level is provided.
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $logLevel = $this->normalizeLogLevel($level);
        $logEntry = new LogEntry(
            $logLevel,
            (string)$message,
            $context,
            new DateTimeImmutable()
        );
        $this->writeLog($this->formatLogEntry($logEntry));
    }

    /**
     * Normalizes the log level to ensure it's a valid LogLevel enum.
     *
     * @param mixed $level The log level to normalize.
     * @return LogLevel The normalized LogLevel enum.
     * @throws InvalidArgumentException If an invalid log level is provided.
     */
    private function normalizeLogLevel(mixed $level): LogLevel
    {
        if ($level instanceof LogLevel) {
            return $level;
        }

        if (is_string($level) || is_int($level)) {
            try {
                return LogLevel::from($level);
            } catch (ValueError $e) {
                throw new InvalidArgumentException("Invalid log level provided: $level", 0, $e);
            }
        }

        throw new InvalidArgumentException('Invalid log level type. Expected LogLevel enum, string, or int.');
    }

    /**
     * Writes a formatted log message to the log file.
     *
     * @param string $message The formatted log message to write.
     * @throws RuntimeException If unable to write to the log file.
     */
    private function writeLog(string $message): void
    {
        $result = file_put_contents($this->logFile, $message, FILE_APPEND);
        if ($result === false) {
            throw new RuntimeException('Unable to write to log file: ' . $this->logFile);
        }
    }

    /**
     * Formats a LogEntry object into a string for logging.
     *
     * @param LogEntry $entry The LogEntry object to format.
     * @return string The formatted log entry as a string.
     */
    private function formatLogEntry(LogEntry $entry): string
    {
        $message = $this->interpolate($entry->message, $entry->context);
        return sprintf(
            "[%s] %s: %s\n",
            $entry->timestamp->format('Y-m-d\TH:i:s.uP'),
            $entry->level->value,
            $message
        );
    }

    /**
     * Interpolates context values into the message placeholders.
     *
     * @param string $message The message with placeholders.
     * @param array $context An array of context data to replace placeholders.
     * @return string The interpolated message.
     */
    private function interpolate(string $message, array $context): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (is_string($val) || $val instanceof Stringable) {
                $replace['{' . $key . '}'] = (string)$val;
            }
        }
        return strtr($message, $replace);
    }

    /**
     * Logs an alert message.
     *
     * @param string|Stringable $message The log message.
     * @param array $context Additional context data for the log entry.
     */
    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Logs a critical message.
     *
     * @param string|Stringable $message The log message.
     * @param array $context Additional context data for the log entry.
     */
    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Logs an error message.
     *
     * @param string|Stringable $message The log message.
     * @param array $context Additional context data for the log entry.
     */
    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Logs a warning message.
     *
     * @param string|Stringable $message The log message.
     * @param array $context Additional context data for the log entry.
     */
    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Logs a notice message.
     *
     * @param string|Stringable $message The log message.
     * @param array $context Additional context data for the log entry.
     */
    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Logs an info message.
     *
     * @param string|Stringable $message The log message.
     * @param array $context Additional context data for the log entry.
     */
    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Logs a debug message.
     *
     * @param string|Stringable $message The log message.
     * @param array $context Additional context data for the log entry.
     */
    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }
}
