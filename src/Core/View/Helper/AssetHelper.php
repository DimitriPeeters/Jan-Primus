<?php

declare(strict_types=1);

namespace AEFS\Core\View\Helper;

final class AssetHelper
{
    public function __construct(
        private readonly string $baseUrl = '',
        private readonly string $assetPath = 'assets'
    ) {
    }

    public function url(string $path): string
    {
        $path = ltrim($path, '/');

        $baseUrl = rtrim($this->baseUrl, '/');
        $assetPath = trim($this->assetPath, '/');

        $segments = array_filter(
            [$baseUrl, $assetPath, $path],
            static fn (string $segment): bool => $segment !== ''
        );

        return implode('/', $segments);
    }

    public function css(string $path): string
    {
        return sprintf(
            '<link rel="stylesheet" href="%s">',
            $this->escape($this->url($path))
        );
    }

    public function js(
        string $path,
        bool $defer = true,
        bool $module = false
    ): string {
        $attributes = [];

        if ($defer) {
            $attributes[] = 'defer';
        }

        if ($module) {
            $attributes[] = 'type="module"';
        }

        $attributeString = $attributes === []
            ? ''
            : ' ' . implode(' ', $attributes);

        return sprintf(
            '<script src="%s"%s></script>',
            $this->escape($this->url($path)),
            $attributeString
        );
    }

    private function escape(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}