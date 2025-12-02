<?php
declare(strict_types=1);

namespace App\Http;

final class JsonResponse
{
    private const JSON_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    public static function send(int $status, array $data): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, self::JSON_FLAGS);
    }

    public static function ok(array $data): void
    {
        self::send(200, $data);
    }

    public static function created(array $data): void
    {
        self::send(201, $data);
    }

    public static function noContent(): void
    {
        http_response_code(204);
        header('Content-Type: application/json; charset=utf-8');
    }

    public static function badRequest(array $errors): void
    {
        self::send(400, ['errors' => $errors]);
    }

    public static function unprocessable(array $errors): void
    {
        self::send(422, ['errors' => $errors]);
    }

    public static function conflict(string $message): void
    {
        self::send(409, ['error' => $message]);
    }

    public static function notFound(string $message = 'Not Found'): void
    {
        self::send(404, ['error' => $message]);
    }

    public static function internalError(string $message = 'Server Error'): void
    {
        self::send(500, ['error' => $message]);
    }
}

