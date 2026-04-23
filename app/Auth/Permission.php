<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Session;

/**
 * Centralised permission helper.
 * All role checks MUST go through this class — never read $_SESSION directly.
 */
class Permission
{
    /**
     * Discord role IDs that always have full access.
     *
     * @var array<int,string>
     */
    public const VEDENI_ROLE_IDS = [
        '1491082314875408475',
        '1491082375197884587',
    ];

    /**
     * Check whether the current user is logged in (has an active session).
     *
     * @return bool
     */
    public static function isLoggedIn(): bool
    {
        return Session::has('user_id');
    }

    /**
     * Check whether the current user has the given Discord role name.
     *
     * @param string $roleName The Discord role name to check (case-sensitive).
     * @return bool
     */
    public static function hasRole(string $roleName): bool
    {
        return static::hasAnyRole([$roleName]);
    }

    /**
     * Check whether the current user has at least one of the given role names.
     *
     * @param array<string> $roleNames Role names to check (case-sensitive, OR logic).
     * @return bool
     */
    public static function hasAnyRole(array $roleNames): bool
    {
        if (!static::isLoggedIn()) {
            return false;
        }

        $roles = Session::get('roles', []);

        if (!is_array($roles)) {
            return false;
        }

        return !empty(array_intersect($roleNames, $roles));
    }

    /**
     * Check whether the current user has at least one of the given Discord role IDs.
     *
     * @param array<string> $roleIds Role IDs to check (OR logic).
     */
    public static function hasAnyRoleIds(array $roleIds): bool
    {
        if (!static::isLoggedIn()) {
            return false;
        }

        $sessionRoleIds = static::roleIds();
        if (empty($sessionRoleIds)) {
            return false;
        }

        return !empty(array_intersect($roleIds, $sessionRoleIds));
    }

    /**
     * Check whether current user belongs to fixed Vedeni roles.
     */
    public static function isVedeni(): bool
    {
        return static::hasAnyRoleIds(self::VEDENI_ROLE_IDS);
    }

    /**
     * Return Discord role IDs from the active session.
     *
     * @return array<int,string>
     */
    public static function roleIds(): array
    {
        $ids = Session::get('role_ids', []);
        if (!is_array($ids)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $ids), static fn(string $v): bool => $v !== ''));
    }

    /**
     * Return the logged-in user's numeric DB id, or null if not logged in.
     *
     * @return int|null
     */
    public static function userId(): ?int
    {
        $id = Session::get('user_id');
        return $id !== null ? (int)$id : null;
    }

    /**
     * Return the logged-in user's Discord snowflake id, or null.
     *
     * @return string|null
     */
    public static function discordId(): ?string
    {
        return Session::get('discord_id');
    }
}
