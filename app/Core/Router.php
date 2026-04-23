<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Front-controller router.
 * Matches the request URI to a registered route and dispatches to the
 * corresponding controller action, optionally running middleware first.
 */
class Router
{
    /** @var array<int, array{method:string, pattern:string, handler:callable|array, middleware:array}> */
    private array $routes = [];

    /**
     * Register a GET route.
     *
     * @param string                $pattern    URI pattern (supports :param segments).
     * @param callable|array        $handler    Controller callback or [ClassName, 'method'].
     * @param array<string>         $middleware List of middleware class names to run.
     */
    public function get(string $pattern, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $pattern, $handler, $middleware);
    }

    /**
     * Register a POST route.
     *
     * @param string                $pattern    URI pattern (supports :param segments).
     * @param callable|array        $handler    Controller callback or [ClassName, 'method'].
     * @param array<string>         $middleware List of middleware class names to run.
     */
    public function post(string $pattern, callable|array $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $pattern, $handler, $middleware);
    }

    /**
     * Add a route to the routing table.
     *
     * @param string         $method     HTTP method.
     * @param string         $pattern    URI pattern.
     * @param callable|array $handler    Handler.
     * @param array<string>  $middleware Middleware list.
     */
    private function addRoute(string $method, string $pattern, callable|array $handler, array $middleware): void
    {
        $this->routes[] = [
            'method'     => $method,
            'pattern'    => $pattern,
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * Dispatch the current HTTP request.
     * Resolves URI parameters, runs middleware, then calls the handler.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $params = $this->match($route['pattern'], $uri);

            if ($params === null) {
                continue;
            }

            // Run middleware stack.
            foreach ($route['middleware'] as $middlewareEntry) {
                if (is_object($middlewareEntry)) {
                    $middlewareEntry->handle();
                } else {
                    $mw = new $middlewareEntry();
                    $mw->handle();
                }
            }

            $this->callHandler($route['handler'], $params);
            return;
        }

        // No route matched — 404.
        http_response_code(404);
        $view = dirname(__DIR__) . '/Views/errors/404.php';
        if (file_exists($view)) {
            require $view;
        } else {
            echo '<h1>404 — Stránka nenalezena</h1>';
        }
    }

    /**
     * Match a URI against a pattern and extract named parameters.
     * Returns an assoc array of params on match, null otherwise.
     *
     * @param string $pattern Route pattern (e.g. /user/:id).
     * @param string $uri     Actual request URI.
     * @return array<string,string>|null
     */
    private function match(string $pattern, string $uri): ?array
    {
        // Escape slashes, then replace :param tokens with named capture groups.
        $regex = preg_replace('#:([a-zA-Z_][a-zA-Z0-9_]*)#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            // Keep only named (string-keyed) params.
            return array_filter($matches, fn($k) => is_string($k), ARRAY_FILTER_USE_KEY);
        }

        return null;
    }

    /**
     * Invoke the route handler with the resolved parameters.
     *
     * @param callable|array       $handler Route handler.
     * @param array<string,string> $params  URI parameters.
     */
    private function callHandler(callable|array $handler, array $params): void
    {
        if (is_callable($handler)) {
            $handler($params);
            return;
        }

        [$class, $method] = $handler;
        $controller = new $class();
        $controller->$method($params);
    }
}
