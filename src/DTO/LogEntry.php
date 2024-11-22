<?php

namespace CreativeCrafts\EmailService\DTO;

use DateTimeImmutable;

final readonly class LogEntry
{
    /**
     * Constructs a new LogEntry instance.
     * This constructor initializes a new LogEntry object with the given log level,
     * message, context, and timestamp.
     *
     * @param LogLevel $level The severity level of the log entry.
     * @param string $message The main message of the log entry.
     * @param array $context Additional contextual information related to the log entry.
     * @param DateTimeImmutable $timestamp The date and time when the log entry was created.
     */
    public function __construct(
        public LogLevel $level,
        public string $message,
        public array $context,
        public DateTimeImmutable $timestamp
    ) {
    }
}
