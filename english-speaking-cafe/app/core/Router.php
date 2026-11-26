<?php
/** Routeur minimaliste : GET/POST + paramètres {id} */
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];

    public function get(string $p, callable|array $h): void { $this->add('GET', $p, $h); }
    public function post(string $p, callable|array $h): void { $this->add('POST', $p, $h); }
    public function any(string $p, callable|array $h): void { $this->add('GET', $p, $h); $this->add('POST', $p, $h); }

    private function add(string $m, string $p, callable|array $h): void
    {
        $regex = '#^' . preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[^/]+)', rtrim($p, '/') ?: '/') . '$#';
        $this->routes[] = [$m, $regex, $h];
    }

    public static function currentPath(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = base_path();
        if ($base !== '' && str_starts_with($uri, $base)) $uri = substr($uri, strlen($base));
        $uri = '/' . trim(rawurldecode($uri), '/');
        if ($uri === '/index.php') $uri = '/';
        return $uri;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = self::currentPath();
        foreach ($this->routes as [$m, $regex, $h]) {
            if ($m !== $method || !preg_match($regex, $path, $mm)) continue;
            $params = array_filter($mm, 'is_string', ARRAY_FILTER_USE_KEY);
            if ($method === 'POST' && !str_starts_with($path, '/api/') && $path !== '/stripe/webhook') Csrf::verify();
            if (is_array($h)) { [$c, $a] = $h; (new $c())->$a(...array_values($params)); }
            else $h(...array_values($params));
            return;
        }
        abort(404, __('This page took a coffee break.'));
    }
}
