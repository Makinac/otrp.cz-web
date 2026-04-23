<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\Permission;
use App\Core\Session;

/**
 * Authentication middleware.
 * Redirects unauthenticated requests to the login page.
 */
class AuthMiddleware
{
    /**
     * Handle the incoming request.
     * If the user is not logged in, redirect to /login and exit.
     */
    public function handle(): void
    {
        if (!Permission::isLoggedIn()) {
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            // Only store local paths for post-login redirect
            if ($uri !== '/' && $uri !== '/login' && str_starts_with($uri, '/') && !str_starts_with($uri, '//')) {
                Session::set('redirect_after_login', $uri);
            }
            header('Location: /login');
            exit;
        }
    }
}
