<?php

declare(strict_types=1);

namespace AEFS\Core\Http;

use JsonException;

class Request
{
    public readonly ParameterBag $query;
    public readonly ParameterBag $request;
    public readonly ParameterBag $attributes;
    public readonly ServerBag $server;
    public readonly HeaderBag $headers;
    public readonly CookieBag $cookies;
    public readonly FileBag $files;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $json = null;

    /**
     * @var array<string, string>
     */
    private array $routeParameters = [];

    public function __construct(
        ?array $query = null,
        ?array $request = null,
        ?array $server = null,
        ?array $cookies = null,
        ?array $files = null
    ) {
        $this->query = new ParameterBag(
            $query ?? $_GET
        );

        $this->request = new ParameterBag(
            $request ?? $_POST
        );

        $this->attributes = new ParameterBag();

        $server ??= $_SERVER;

        $this->server = new ServerBag($server);

        $this->headers = new HeaderBag(
            $this->extractHeaders($server)
        );

        $this->cookies = new CookieBag(
            $cookies ?? $_COOKIE
        );

        $this->files = new FileBag(
            $files ?? $_FILES
        );
    }

    public static function capture(): self
    {
        return new self();
    }

    public function method(): string
    {
        return $this->server->method();
    }

    public function uri(): string
    {
        return $this->server->uri();
    }

    public function path(): string
    {
        $path = $this->server->path();

        $scriptName = str_replace(
            '\\',
            '/',
            (string) (
                $this->server->get(
                    'SCRIPT_NAME',
                    $_SERVER['SCRIPT_NAME'] ?? ''
                )
            )
        );

        $basePath = str_replace(
            '\\',
            '/',
            dirname($scriptName)
        );

        if (
            $basePath !== '/'
            && $basePath !== '.'
            && str_starts_with($path, $basePath)
        ) {
            $path = substr(
                $path,
                strlen($basePath)
            );
        }

        $path = '/' . ltrim(
            $path,
            '/'
        );

        if ($path === '/') {
            return '/';
        }

        return rtrim($path, '/');
    }

    public function host(): string
    {
        return $this->server->host();
    }

    public function scheme(): string
    {
        return $this->server->scheme();
    }

    public function url(): string
    {
        return sprintf(
            '%s://%s%s',
            $this->scheme(),
            $this->host(),
            $this->path()
        );
    }

    public function fullUrl(): string
    {
        $query = $this->server->queryString();

        return $query === ''
            ? $this->url()
            : $this->url() . '?' . $query;
    }

    public function input(
        string $key,
        mixed $default = null
    ): mixed {
        if ($this->request->has($key)) {
            return $this->request->get($key);
        }

        if ($this->query->has($key)) {
            return $this->query->get($key);
        }

        $json = $this->json();

        return $json[$key] ?? $default;
    }

    public function post(
        ?string $key = null,
        mixed $default = null
    ): mixed {
        if ($key === null) {
            return $this->request->all();
        }

        return $this->request->get(
            $key,
            $default
        );
    }

    public function get(
        ?string $key = null,
        mixed $default = null
    ): mixed {
        if ($key === null) {
            return $this->query->all();
        }

        return $this->query->get(
            $key,
            $default
        );
    }

    /**
     * @param array<string, string> $parameters
     */
    public function setRouteParameters(
        array $parameters
    ): void {
        $this->routeParameters = $parameters;
    }

    public function route(
        ?string $key = null,
        mixed $default = null
    ): mixed {
        if ($key === null) {
            return $this->routeParameters;
        }

        return $this->routeParameters[$key]
            ?? $default;
    }

    public function hasRouteParameter(string $key): bool
    {
        return array_key_exists(
            $key,
            $this->routeParameters
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return array_merge(
            $this->query->all(),
            $this->request->all(),
            $this->json()
        );
    }

    /**
     * @param list<string> $keys
     *
     * @return array<string, mixed>
     */
    public function only(array $keys): array
    {
        $data = [];

        foreach ($keys as $key) {
            $value = $this->input($key);

            if ($value !== null) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * @param list<string> $keys
     *
     * @return array<string, mixed>
     */
    public function except(array $keys): array
    {
        $data = $this->all();

        foreach ($keys as $key) {
            unset($data[$key]);
        }

        return $data;
    }

    public function file(
        string $key
    ): UploadedFile|array|null {
        return $this->files->get($key);
    }

    public function header(
        string $key,
        ?string $default = null
    ): ?string {
        return $this->headers->get(
            $key,
            $default
        );
    }

    public function cookie(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->cookies->get(
            $key,
            $default
        );
    }

    public function isMethod(string $method): bool
    {
        return strtoupper($method)
            === strtoupper($this->method());
    }

    public function isAjax(): bool
    {
        return $this->server->isAjax();
    }

    public function acceptsJson(): bool
    {
        return $this->server->acceptsJson();
    }

    public function ip(): string
    {
        return $this->server->ip();
    }

    public function bearerToken(): ?string
    {
        return $this->server->bearerToken();
    }

    /**
     * @return array<string, mixed>
     */
    public function json(): array
    {
        if ($this->json !== null) {
            return $this->json;
        }

        $body = file_get_contents(
            'php://input'
        );

        if ($body === false || $body === '') {
            return $this->json = [];
        }

        try {
            $decoded = json_decode(
                $body,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            return $this->json = [];
        }

        return $this->json = is_array($decoded)
            ? $decoded
            : [];
    }

    /**
     * @param array<string, mixed> $server
     *
     * @return array<string, string>
     */
    private function extractHeaders(array $server): array
    {
        $headers = [];

        foreach ($server as $key => $value) {
            if (!str_starts_with((string) $key, 'HTTP_')) {
                continue;
            }

            $name = str_replace(
                '_',
                '-',
                substr((string) $key, 5)
            );

            $headers[$name] = (string) $value;
        }

        if (isset($server['CONTENT_TYPE'])) {
            $headers['Content-Type'] = (string) $server['CONTENT_TYPE'];
        }

        if (isset($server['CONTENT_LENGTH'])) {
            $headers['Content-Length'] = (string) $server['CONTENT_LENGTH'];
        }

        if (isset($server['CONTENT_MD5'])) {
            $headers['Content-MD5'] = (string) $server['CONTENT_MD5'];
        }

        return $headers;
    }
}