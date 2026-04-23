<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session manager.
 * Starts the session with secure options and provides helper methods.
 */
class Session
{
    /**
     * Start the PHP session with hardened cookie parameters.
     * Must be called before any output.
     */
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();

        // Regenerate session ID on first creation to prevent fixation.
        if (empty($_SESSION['_initiated'])) {
            session_regenerate_id(true);
            $_SESSION['_initiated'] = true;
            $_SESSION['_created_at'] = time();
        }

        // Periodically regenerate session ID (every 30 minutes) to limit session hijacking window.
        if (isset($_SESSION['_created_at']) && (time() - $_SESSION['_created_at']) > 1800) {
            session_regenerate_id(true);
            $_SESSION['_created_at'] = time();
        }

        // Idle timeout: destroy session after 4 hours of inactivity.
        if (isset($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity']) > 14400) {
            self::destroy();
            self::start();
            return;
        }
        $_SESSION['_last_activity'] = time();
    }

    /**
     * Store a value in the session.
     *
     * @param string $key   Session key.
     * @param mixed  $value Value to store.
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Retrieve a value from the session.
     *
     * @param string $key     Session key.
     * @param mixed  $default Returned when the key is absent.
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Remove a key from the session.
     *
     * @param string $key Session key to remove.
     */
    public static function delete(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Check whether a session key exists and is not null.
     *
     * @param string $key Session key.
     * @return bool
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Destroy the current session completely (logout).
     */
    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    /**
     * Generate and store a CSRF token, or return the existing one.
     *
     * @return string The CSRF token.
     */
    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    /**
     * Validate a submitted CSRF token against the stored one.
     * Constant-time comparison to prevent timing attacks.
     *
     * @param string $token Submitted token.
     * @return bool
     */
    public static function verifyCsrf(string $token): bool
    {
        $stored = $_SESSION['_csrf_token'] ?? '';
        return hash_equals($stored, $token);
    }

    /**
     * Store a one-time flash message.
     *
     * @param string $type    Message type (e.g. 'success', 'error').
     * @param string $message Message text.
     */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][$type] = $message;
    }

    /**
     * Retrieve and clear all flash messages.
     *
     * @return array<string,string>
     */
    public static function getFlash(): array
    {
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flash;
    }

    /**
     * Simple session-based rate limiter.
     * Returns true if the action is allowed, false if rate limit exceeded.
     *
     * @param string $action  Unique identifier for the action being rate-limited.
     * @param int    $maxAttempts Maximum attempts allowed within the window.
     * @param int    $windowSeconds Time window in seconds.
     * @return bool True if allowed, false if rate-limited.
     */
    public static function rateLimit(string $action, int $maxAttempts, int $windowSeconds): bool
    {
        $key = '_rate_' . $action;
        $now = time();

        $data = $_SESSION[$key] ?? ['attempts' => [], 'blocked_until' => 0];

        // If currently blocked, check if block has expired
        if ($data['blocked_until'] > $now) {
            return false;
        }

        // Clean old attempts outside the window
        $data['attempts'] = array_values(array_filter(
            $data['attempts'],
            static fn(int $ts): bool => ($now - $ts) < $windowSeconds
        ));

        if (count($data['attempts']) >= $maxAttempts) {
            // Block for the window duration
            $data['blocked_until'] = $now + $windowSeconds;
            $_SESSION[$key] = $data;
            return false;
        }

        $data['attempts'][] = $now;
        $data['blocked_until'] = 0;
        $_SESSION[$key] = $data;
        return true;
    }
}
