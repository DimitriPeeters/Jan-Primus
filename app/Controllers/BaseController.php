<?php

declare(strict_types=1);

namespace App\Controllers;

use AEFS\Core\Http\RedirectResponse;
use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;
use AEFS\Core\Session;
use AEFS\Core\View\ViewFactory;

abstract class BaseController
{
    public function __construct(
        protected readonly ViewFactory $views,
        protected readonly Request $request
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    protected function view(
        string $view,
        array $data = [],
        int $status = 200,
        array $headers = []
    ): Response {
        return $this->views->response(
            $view,
            $data,
            $status,
            $headers
        );
    }

    protected function redirect(
        string $url,
        int $status = 302
    ): RedirectResponse {
        return new RedirectResponse(
            $this->resolveRedirectUrl($url),
            $status
        );
    }

    protected function request(): Request
    {
        return $this->request;
    }

    protected function input(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->request->input(
            $key,
            $default
        );
    }

    protected function post(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->request->post(
            $key,
            $default
        );
    }

    protected function flash(
        string $type,
        string $message
    ): void {
        Session::flash(
            $type,
            $message
        );
    }

    protected function success(string $message): void
    {
        $this->flash(
            'success',
            $message
        );
    }

    protected function error(string $message): void
    {
        $this->flash(
            'error',
            $message
        );
    }

    protected function warning(string $message): void
    {
        $this->flash(
            'warning',
            $message
        );
    }

    protected function info(string $message): void
    {
        $this->flash(
            'info',
            $message
        );
    }

    private function resolveRedirectUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return $this->applicationBasePath() . '/';
        }

        if (
            preg_match('#^[a-z][a-z0-9+\-.]*://#i', $url) === 1
            || str_starts_with($url, '//')
        ) {
            return $url;
        }

        $basePath = $this->applicationBasePath();

        if (
            $basePath !== ''
            && (
                $url === $basePath
                || str_starts_with($url, $basePath . '/')
            )
        ) {
            return $url;
        }

        return rtrim($basePath, '/')
            . '/'
            . ltrim($url, '/');
    }

    private function applicationBasePath(): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

        if (!is_string($scriptName) || $scriptName === '') {
            return '';
        }

        $directory = str_replace(
            '\\',
            '/',
            dirname($scriptName)
        );

        if ($directory === '/' || $directory === '.') {
            return '';
        }

        return rtrim($directory, '/');
    }
}