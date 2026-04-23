<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth\Permission;
use App\Core\Session;
use App\Models\ManagementPermissionModel;

/**
 * Base controller providing shared view rendering to all child controllers.
 */
abstract class BaseController
{
    /**
     * Render a view file by partial path, passing variables into its scope.
     *
     * @param string               $view      Path relative to app/Views/ (without .php).
     * @param array<string,mixed>  $data      Variables to extract into the view scope.
     * @param string               $layout    Layout file relative to app/Views/layouts/ (without .php).
     */
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        // Make flash messages available to all views.
        $data['flash']     = Session::getFlash();
        $data['csrfToken'] = Session::csrfToken();
        $data['isLoggedIn'] = Permission::isLoggedIn();
        $data['currentUserId'] = Permission::isLoggedIn() ? (int)Permission::userId() : null;
        $data['hasRoleVedeni']   = Permission::isVedeni();
        $data['hasManagementAccess'] = $data['hasRoleVedeni'];
        $data['hasAdminAccess']  = $data['hasRoleVedeni'];
        $data['hasRoleTester']   = $data['hasAdminAccess'];
        $data['hasIngameAdmin']  = $data['hasRoleVedeni'];



        if ((!$data['hasManagementAccess'] || !$data['hasAdminAccess']) && $data['isLoggedIn']) {
            $roles = Session::get('roles', []);
            $roles = is_array($roles) ? array_values(array_map('strval', $roles)) : [];
            $roleIds = Permission::roleIds();
            $keys    = (new ManagementPermissionModel())->getPermissionKeysForUser((int)Permission::userId(), $roles, $roleIds);

            if (!$data['hasManagementAccess']) {
                foreach ($keys as $key) {
                    if (str_starts_with((string)$key, 'management.')) {
                        $data['hasManagementAccess'] = true;
                        break;
                    }
                }
            }

            if (!$data['hasAdminAccess']) {
                foreach ($keys as $key) {
                    if (str_starts_with((string)$key, 'admin.')) {
                        $data['hasAdminAccess'] = true;
                        break;
                    }
                }
                $data['hasRoleTester'] = $data['hasAdminAccess'];
            }

            if (!$data['hasIngameAdmin']) {
                $data['hasIngameAdmin'] = in_array('ingame.admin', $keys, true);
            }
        }

        extract($data, EXTR_SKIP);

        $viewFile = dirname(__DIR__) . "/Views/{$view}.php";
        $layoutFile = dirname(__DIR__) . "/Views/layouts/{$layout}.php";

        // Prevent path traversal in view names
        $realView = realpath($viewFile);
        $viewsDir = realpath(dirname(__DIR__) . '/Views');
        if ($realView === false || $viewsDir === false || !str_starts_with($realView, $viewsDir)) {
            http_response_code(500);
            echo "View not found: " . htmlspecialchars($view);
            return;
        }

        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo "View not found: {$view}";
            return;
        }

        // Capture the view content.
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Render inside layout.
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    /**
     * Redirect to a local URL and stop execution.
     * Only allows relative (local) paths to prevent open redirect.
     *
     * @param string $url Target URL path.
     */
    protected function redirect(string $url): never
    {
        // Prevent open redirect: allow only local paths
        if (!str_starts_with($url, '/') || str_starts_with($url, '//')) {
            $url = '/';
        }
        header("Location: {$url}");
        exit;
    }

    /**
     * Redirect to a trusted external URL and stop execution.
     * Only allows URLs matching the configured trusted hosts.
     *
     * @param string   $url          Target URL.
     * @param string[] $trustedHosts Allowed hostnames.
     */
    protected function externalRedirect(string $url, array $trustedHosts): never
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';
        if ($host !== '' && in_array($host, $trustedHosts, true)) {
            header("Location: {$url}");
            exit;
        }
        // Fallback to homepage if host is not trusted
        header('Location: /');
        exit;
    }

    /**
     * Return a JSON response and stop execution.
     *
     * @param mixed $data       Data to encode.
     * @param int   $statusCode HTTP status code.
     */
    protected function json(mixed $data, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function requirePost(string $fallbackUrl = '/'): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect($fallbackUrl);
        }
    }

    protected function verifyCsrf(string $fallbackUrl = '/'): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!Session::verifyCsrf($token)) {
            Session::flash('error', 'Neplatný CSRF token.');
            $this->redirect($fallbackUrl);
        }
    }
}
