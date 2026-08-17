<?php

declare(strict_types=1);

namespace AEFS\Core\Http;

final class RedirectResponse extends Response
{
    public function __construct(
        string $url,
        int $statusCode = 302,
        array $headers = []
    ) {
        $headers['Location'] = $url;

        parent::__construct(
            '',
            $statusCode,
            $headers
        );
    }

    public function withStatus(int $statusCode): self
    {
        $this->setStatus($statusCode);

        return $this;
    }

    public function withHeader(
        string $name,
        string|array $value
    ): self {
        $this->header($name, $value);

        return $this;
    }

    public function permanent(): self
    {
        $this->setStatus(301);

        return $this;
    }

    public function temporary(): self
    {
        $this->setStatus(302);

        return $this;
    }

    public function seeOther(): self
    {
        $this->setStatus(303);

        return $this;
    }

    public function temporaryPreserveMethod(): self
    {
        $this->setStatus(307);

        return $this;
    }

    public function permanentPreserveMethod(): self
    {
        $this->setStatus(308);

        return $this;
    }

    public function back(): self
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';

        $this->header('Location', $referer);

        return $this;
    }

    public function refresh(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        $this->header('Location', $uri);

        return $this;
    }

    public function route(string $url): self
    {
        $this->header('Location', $url);

        return $this;
    }
}