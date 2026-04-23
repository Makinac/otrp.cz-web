<?php

declare(strict_types=1);

/**
 * Application configuration loader.
 * Reads .env file from project root and provides typed access to config values.
 */

$envFile = dirname(__DIR__) . '/.env';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);
            if (!empty($key)) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

/**
 * Retrieve an environment variable with an optional default.
 *
 * @param string $key     The variable name.
 * @param mixed  $default Returned when the variable is not set.
 * @return mixed
 */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? false;
    if ($value === false) {
        $value = getenv($key);
    }
    return ($value !== false && $value !== '') ? $value : $default;
}
