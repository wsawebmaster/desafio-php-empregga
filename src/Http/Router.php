<?php
declare(strict_types=1);

namespace App\Http;

final class Router
{
    /** @var array<int, array{0: string, 1: array<string, mixed>, 2: callable}> */
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            strtoupper($method),
            $this->compilePattern($pattern),
            $handler,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        
        foreach ($this->routes as [$m, $compiled, $handler]) {
            if ($m !== strtoupper($method)) {
                continue;
            }
            
            if (preg_match($compiled['regex'], $path, $matches)) {
                $params = [];
                foreach ($compiled['keys'] as $i => $key) {
                    $params[$key] = $matches[$i + 1] ?? null;
                }
                $handler($params);
                return;
            }
        }
        
        JsonResponse::notFound();
    }

    private function compilePattern(string $pattern): array
    {
        $keys = [];
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#',
            function ($matches) use (&$keys): string {
                $keys[] = $matches[1];
                return '([\\w-]+)';
            },
            $pattern
        );
        
        return [
            'regex' => '#^' . $regex . '$#',
            'keys' => $keys,
        ];
    }
}

