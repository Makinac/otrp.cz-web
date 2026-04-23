<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple file-based application logger.
 * Errors are written to logs/app.log — never echoed to the user.
 */
class Logger
{
    private static string $logFile = '';

    /**
     * Initialise the log file path (called once from the bootstrap).
     *
     * @param string $path Absolute path to the log file.
     */
    public static function init(string $path): void
    {
        static::$logFile = $path;
    }

    /**
     * Write an ERROR-level entry to the log.
     *
     * @param string $message Human-readable error description.
     */
    public static function error(string $message): void
    {
        static::write('ERROR', $message);
    }

    /**
     * Write an INFO-level entry to the log.
     *
     * @param string $message Human-readable info message.
     */
    public static function info(string $message): void
    {
        static::write('INFO', $message);
    }

    /**
     * Write a WARNING-level entry to the log.
     *
     * @param string $message Human-readable warning message.
     */
    public static function warning(string $message): void
    {
        static::write('WARNING', $message);
    }

    /**
     * Append a formatted line to the log file.
     *
     * @param string $level   Severity level label.
     * @param string $message Log message.
     */
    private static function write(string $level, string $message): void
    {
        $path = static::$logFile ?: dirname(__DIR__, 2) . '/logs/app.log';
        $line = sprintf('[%s] [%s] %s' . PHP_EOL, date('Y-m-d H:i:s'), $level, $message);
        @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }
}
