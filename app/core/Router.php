<?php
declare(strict_types=1);

class Router
{
    private string $basePath = '';
    private array $routes = [];   // [$method][] = ['regex'=>..., 'params'=>[], 'handler'=>callable]
    private $notFoundHandler = null;

    public function setBasePath(string $basePath): void {
        $this->basePath = rtrim($basePath, '/');
    }

    public function get(string $pattern, $handler): self  { return $this->map('GET',  $pattern, $handler); }
    public function post(string $pattern, $handler): self { return $this->map('POST', $pattern, $handler); }
    public function any(string $pattern, $handler): self  { $this->get($pattern,$handler); $this->post($pattern,$handler); return $this; }

    public function map(string $method, string $pattern, $handler): self
    {
        // Convert /foo/{id} to regex with named captures
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . rtrim($this->basePath . $regex, '/') . '/?$#';

        $this->routes[strtoupper($method)][] = [
            'pattern' => $pattern,
            'regex'   => $regex,
            'handler' => $handler,
        ];
        return $this;
    }

    public function setNotFound(callable $handler): void {
        $this->notFoundHandler = $handler;
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uriRaw = $_SERVER['REQUEST_URI'] ?? '/';
        $uri    = strtok($uriRaw, '?'); // match routes on path only
        $routes = $this->routes[$method] ?? [];

        // Try registered routes first
        foreach ($routes as $r) {
            if (preg_match($r['regex'], $uri, $m)) {
                // Collect named params only
                $params = [];
                foreach ($m as $k => $v) if (!is_int($k)) $params[$k] = $v;
                $this->invoke($r['handler'], $params);
                return;
            }
        }

        // ---- Fallbacks (keep app working without new route registrations) ----
        // Normalize path without basePath for file lookups
        $path = $this->stripBasePath($uri);

        // 1) Special-case: /board -> load views/board_show.php (uses $_GET['id'])
        if ($path === '/board') {
            $viewFile = $this->resolveView('board_show.php');
            if ($viewFile) { require $viewFile; return; }
        }

        // 2) Direct view passthrough, e.g. /board_show.php
        if (str_ends_with($path, '.php')) {
            $viewFile = $this->resolveView(ltrim($path, '/'));
            if ($viewFile) { require $viewFile; return; }
        }
        // ---------------------------------------------------------------------

        // 404
        if ($this->notFoundHandler) {
            http_response_code(404);
            call_user_func($this->notFoundHandler, $uri);
            return;
        }
        http_response_code(404);
        echo "Not Found";
    }

    private function invoke($handler, array $params): void
    {
        // Controller class + method: [ControllerClass::class, 'method'] or ['ControllerClass','method']
        if (is_array($handler) && count($handler) === 2 && is_string($handler[0])) {
            $class  = $handler[0];
            $method = $handler[1];
            $obj    = new $class();
            $obj->$method(...array_values($params));
            return;
        }
        // Callable function/closure
        if (is_callable($handler)) {
            $handler(...array_values($params));
            return;
        }
        // Direct controller instance + method
        if (is_array($handler) && is_object($handler[0])) {
            [$obj, $method] = $handler;
            $obj->$method(...array_values($params));
            return;
        }
        throw new RuntimeException('Invalid route handler');
    }

    // --- Helpers -------------------------------------------------------------

    private function stripBasePath(string $uri): string
    {
        $path = $uri;
        if ($this->basePath !== '' && str_starts_with($path, $this->basePath)) {
            $path = substr($path, strlen($this->basePath));
            if ($path === false) $path = '/';
        }
        if ($path === '') $path = '/';
        return $path;
    }

    private function resolveView(string $relative): ?string
{
    // Normalize and block traversal
    $relative = ltrim($relative, "/\\");
    if (strpos($relative, '..') !== false) return null;

    // Try common roots: /app/views and /app
    $roots = [
        realpath(__DIR__ . '/../views'), // e.g., /app/views
        realpath(__DIR__ . '/..'),       // e.g., /app  (where board_show.php may be)
    ];

    foreach ($roots as $root) {
        if ($root === false) continue;

        // 1) exact relative path under root
        $candidate = $root . DIRECTORY_SEPARATOR . $relative;
        if (is_file($candidate)) return $candidate;

        // 2) basename fallback (handle requests like "/board_show.php")
        $base = basename($relative);
        $candidate2 = $root . DIRECTORY_SEPARATOR . $base;
        if (is_file($candidate2)) return $candidate2;
    }
    return null;
}
}
