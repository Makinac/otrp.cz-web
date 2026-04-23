<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\Permission;

/**
 * Role-based access middleware.
 * Returns HTTP 403 when the authenticated user does not possess the required
 * Discord role.
 */
class RoleMiddleware
{
    /** @var array<string> */
    private array $requiredRoles;

    /**
     * @param string|array<string> $requiredRoles One role name or array of role names (OR logic).
     */
    public function __construct(string|array $requiredRoles)
    {
        $this->requiredRoles = (array) $requiredRoles;
    }

    /**
     * Handle the incoming request.
     * Requires the user to be logged in AND to have at least one of the required roles.
     * Responds with 403 on failure and stops execution.
     */
    public function handle(): void
    {
        if (!Permission::isLoggedIn()) {
            header('Location: /auth/redirect');
            exit;
        }

        if (!Permission::hasAnyRole($this->requiredRoles)) {
            http_response_code(403);
            require dirname(__DIR__) . '/Views/errors/403.php';
            exit;
        }
    }
}
