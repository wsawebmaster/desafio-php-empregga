<?php
declare(strict_types=1);

namespace App\Http;

final class Request
{
    /** @var array<string, mixed> */
    private array $json = [];

    public function __construct()
    {
        $this->parseJson();
    }

    private function parseJson(): void
    {
        $content = file_get_contents('php://input');
        if ($content) {
            $decoded = json_decode($content, true);
            $this->json = is_array($decoded) ? $decoded : [];
        }
    }

    public function json(string $key, mixed $default = null): mixed
    {
        return $this->json[$key] ?? $default;
    }

    public function jsonAll(): array
    {
        return $this->json;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    public function queryInt(string $key, int $default = 0): int
    {
        return (int)($this->query($key) ?? $default);
    }

    public function queryString(string $key, string $default = ''): string
    {
        return (string)($this->query($key) ?? $default);
    }

    public static function param(string $key, mixed $default = null): mixed
    {
        return $GLOBALS['_route_params'][$key] ?? $default;
    }

    public static function paramInt(string $key, int $default = 0): int
    {
        return (int)(self::param($key) ?? $default);
    }
}
