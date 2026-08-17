<?php

declare(strict_types=1);

namespace AEFS\Core\Http;

use JsonSerializable;
use Stringable;

class Response
{
    protected int $statusCode;

    protected string $content;

    protected HeaderBag $headers;

    public function __construct(
        string|Stringable $content = '',
        int $statusCode = 200,
        array $headers = []
    ) {
        $this->content = (string) $content;
        $this->statusCode = $statusCode;
        $this->headers = new HeaderBag($headers);
    }

    public function content(): string
    {
        return $this->content;
    }

    public function setContent(string|Stringable $content): static
    {
        $this->content = (string) $content;

        return $this;
    }

    public function status(): int
    {
        return $this->statusCode;
    }

    public function setStatus(int $statusCode): static
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    public function headers(): HeaderBag
    {
        return $this->headers;
    }

    public function header(
        string $name,
        string|array $value
    ): static {
        $this->headers->set($name, $value);

        return $this;
    }

    public function json(
        array|JsonSerializable $data,
        int $status = 200
    ): static {
        $this->statusCode = $status;

        $this->headers->set(
            'Content-Type',
            'application/json; charset=UTF-8'
        );

        $this->content = json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_PRETTY_PRINT
        ) ?: '{}';

        return $this;
    }

    public function html(string $html): static
    {
        $this->headers->set(
            'Content-Type',
            'text/html; charset=UTF-8'
        );

        $this->content = $html;

        return $this;
    }

    public function text(string $text): static
    {
        $this->headers->set(
            'Content-Type',
            'text/plain; charset=UTF-8'
        );

        $this->content = $text;

        return $this;
    }

    public function download(
        string $filename,
        string $contents,
        string $mime = 'application/octet-stream'
    ): static {
        $this->headers->set('Content-Type', $mime);
        $this->headers->set(
            'Content-Disposition',
            'attachment; filename="' . basename($filename) . '"'
        );
        $this->headers->set(
            'Content-Length',
            (string) strlen($contents)
        );

        $this->content = $contents;

        return $this;
    }

    public function redirect(
        string $url,
        int $status = 302
    ): static {
        $this->statusCode = $status;

        $this->headers->set('Location', $url);

        return $this;
    }

    public function send(): void
    {
        if (!headers_sent()) {
            http_response_code($this->statusCode);

            $this->headers->send();
        }

        echo $this->content;
    }
}