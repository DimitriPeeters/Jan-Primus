<?php

declare(strict_types=1);

namespace AEFS\Core;

final class Response
{
    public static function html(string $content, int $status = 200): never
    {
        http_response_code($status);

        header('Content-Type: text/html; charset=UTF-8');

        echo $content;

        exit;
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);

        header('Content-Type: application/json');

        echo json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        exit;
    }

    public static function redirect(string $url): never
    {
        header("Location: {$url}");

        exit;
    }
}