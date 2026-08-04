<?php
/**
 * Minimal front-controller router.
 *
 * Routes are matched on the `r` query parameter (e.g. ?r=campaigns/edit),
 * which keeps the app working on shared hosting without URL rewriting.
 * A clean .htaccess rewrite to ?r=$1 is provided for prettier URLs.
 */

declare(strict_types=1);

final class Router
{
    /** @var array<string,array{0:string,1:string}> */
    private array $routes = [];

    public function add(string $route, string $controller, string $action): void
    {
        $this->routes[trim($route, '/')] = [$controller, $action];
    }

    public function dispatch(string $route): void
    {
        $route = trim($route, '/');
        if ($route === '') {
            // Logged-in users land on their dashboard; visitors see the public
            // marketing landing page (which itself links to login / register).
            $route = Auth::check() ? 'dashboard' : 'home';
        }

        if (!isset($this->routes[$route])) {
            http_response_code(404);
            [$controller, $action] = ['ErrorController', 'notFound'];
        } else {
            [$controller, $action] = $this->routes[$route];
        }

        if (!class_exists($controller) || !method_exists($controller, $action)) {
            http_response_code(500);
            exit('Route handler not found: ' . e($controller . '::' . $action));
        }

        (new $controller())->$action();
    }
}
