<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\DiscordOAuth;
use App\Auth\Permission;
use App\Core\Logger;
use App\Core\Session;
use App\Models\UserModel;

/**
 * Handles Discord OAuth2 login and logout flows.
 */
class AuthController extends BaseController
{
    /**
     * Redirect the user to Discord's OAuth2 authorization endpoint.
     * Stores a CSRF state token in the session.
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function oauthRedirect(array $params = []): void
    {
        if (Permission::isLoggedIn()) {
            $this->redirect('/allowlist');
        }

        // Rate limit: max 5 login attempts per 60 seconds.
        if (!Session::rateLimit('oauth_redirect', 5, 60)) {
            Session::flash('error', 'Příliš mnoho pokusů o přihlášení. Zkus to za chvíli.');
            $this->redirect('/login');
        }

        // Generate an OAuth state token to prevent CSRF on the callback.
        $state = bin2hex(random_bytes(16));
        Session::set('oauth_state', $state);

        $url = DiscordOAuth::getAuthUrl() . '&state=' . urlencode($state);
        $this->externalRedirect($url, ['discord.com']);
    }

    /**
     * Handle the Discord OAuth2 callback.
     * Exchanges the code for a token, fetches user info and guild roles,
     * persists them in the database, and initialises the session.
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function callback(array $params = []): void
    {
        $code  = $_GET['code']  ?? '';
        $state = $_GET['state'] ?? '';

        // Validate state to prevent CSRF.
        $storedState = Session::get('oauth_state', '');
        Session::delete('oauth_state');

        if (!hash_equals($storedState, $state) || empty($code)) {
            Logger::warning('OAuth callback: invalid state or missing code.');
            Session::flash('error', 'Přihlášení selhalo — neplatný požadavek.');
            $this->redirect('/login');
        }

        // Exchange code for access token.
        $tokenData = DiscordOAuth::exchangeCode($code);
        if (!$tokenData || empty($tokenData['access_token'])) {
            Logger::error('OAuth callback: token exchange failed.');
            Session::flash('error', 'Přihlášení selhalo — chyba komunikace s Discordem.');
            $this->redirect('/login');
        }

        $accessToken = $tokenData['access_token'];

        // Fetch Discord user profile.
        $discordUser = DiscordOAuth::getUser($accessToken);
        if (!$discordUser || empty($discordUser['id'])) {
            Logger::error('OAuth callback: failed to fetch Discord user.');
            Session::flash('error', 'Nepodařilo se načíst váš Discord účet.');
            $this->redirect('/login');
        }

        $discordId = $discordUser['id'];
        $username  = $discordUser['global_name'] ?? $discordUser['username'] ?? 'Unknown';
        $avatar    = $discordUser['avatar'] ?? null;

        // Upsert user in DB.
        $userModel = new UserModel();
        $userId    = $userModel->upsert($discordId, $username, $avatar);
        $user      = $userModel->findById($userId);

        // Fetch / refresh guild roles.
        $roleNames = [];
        $roleIds   = [];
        $needsRoleRefresh = $user && (
            !$userModel->isRolesCacheFresh($user)
            || empty($user['role_ids_json'])
            || empty($user['roles_json'])
        );

        if ($needsRoleRefresh) {
            $member = DiscordOAuth::getGuildMember($discordId);
            if ($member && isset($member['roles'])) {
                $roleIds   = is_array($member['roles']) ? array_values(array_map('strval', $member['roles'])) : [];
                $roleNames = DiscordOAuth::resolveRoleNames($roleIds);
                $userModel->updateRoles($userId, $roleNames, $roleIds);
            } elseif ($user) {
                // Keep last known values if Discord API is temporarily unavailable.
                $roleNames = $userModel->getRoleNames($user);
                $roleIds   = $userModel->getRoleIds($user);
            }
        } else {
            $roleNames = $user ? $userModel->getRoleNames($user) : [];
            $roleIds   = $user ? $userModel->getRoleIds($user) : [];
        }

        // Initialise session.
        session_regenerate_id(true);
        Session::set('user_id',    $userId);
        Session::set('discord_id', $discordId);
        Session::set('username',   $username);
        Session::set('avatar',     $avatar);
        Session::set('roles',      $roleNames);
        Session::set('role_ids',   $roleIds);

        $redirectTo = Session::get('redirect_after_login', '/allowlist');
        Session::delete('redirect_after_login');
        // Validate redirect target is a local path to prevent open redirect
        if (!is_string($redirectTo) || !str_starts_with($redirectTo, '/') || str_starts_with($redirectTo, '//')) {
            $redirectTo = '/allowlist';
        }
        $this->redirect($redirectTo);
    }

    /**
     * Log the user out by destroying the session.
     *
     * @param array<string,string> $params Route parameters (unused).
     */
    public function logout(array $params = []): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!Session::verifyCsrf($token)) {
                Session::flash('error', 'Neplatný CSRF token.');
                $this->redirect('/allowlist');
            }
        }

        Session::destroy();
        $this->redirect('/');
    }
}
