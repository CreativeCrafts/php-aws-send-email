<?php

namespace CreativeCrafts\EmailService\DTO;

/**
 * Represents different log levels for application logging.
 * This enum defines standard log levels that can be used to categorize
 * log messages based on their severity or importance.
 */
enum LogLevel: string
{
    /**
     * System is unusable.
     */
    case EMERGENCY = 'emergency';

    /**
     * Action must be taken immediately.
     */
    case ALERT = 'alert';

    /**
     * Critical conditions.
     */
    case CRITICAL = 'critical';

    /**
     * Error conditions.
     */
    case ERROR = 'error';

    /**
     * Warning conditions.
     */
    case WARNING = 'warning';

    /**
     * Normal but significant condition.
     */
    case NOTICE = 'notice';

    /**
     * Informational messages.
     */
    case INFO = 'info';

    /**
     * Debug-level messages.
     */
    case DEBUG = 'debug';
}
