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
        $uri    = strtok($_SERVER['REQUEST_URI'] ?? '/', '?'); // strip query string
        $routes = $this->routes[$method] ?? [];

        foreach ($routes as $r) {
            if (preg_match($r['regex'], $uri, $m)) {
                // Collect named params only
                $params = [];
                foreach ($m as $k => $v) if (!is_int($k)) $params[$k] = $v;
                return $this->invoke($r['handler'], $params);
            }
        }
        // 404
        if ($this->notFoundHandler) {
            http_response_code(404);
            return call_user_func($this->notFoundHandler, $uri);
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
}
