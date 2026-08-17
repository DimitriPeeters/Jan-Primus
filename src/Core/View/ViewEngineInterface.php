<?php

declare(strict_types=1);

namespace AEFS\Core\View;

use AEFS\Core\Http\Response;
use AEFS\Core\View\Component\ComponentInterface;
use AEFS\Core\View\Component\Slot;
use AEFS\Core\View\Helper\ViewHelpers;

interface ViewEngineInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function render(string $view, array $data = []): string;

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function response(
        string $view,
        array $data = [],
        int $status = 200,
        array $headers = []
    ): Response;

    public function share(string $key, mixed $value): void;

    /**
     * @param array<string, mixed> $data
     */
    public function shareMany(array $data): void;

    public function exists(string $view): bool;

    /**
     * @param array<string, mixed> $data
     */
    public function include(string $view, array $data = []): string;

    /**
     * @param array<string, mixed> $data
     */
    public function partial(string $view, array $data = []): string;

    /**
     * @param array<string, mixed> $data
     */
    public function extend(string $layout, array $data = []): void;

    public function startSection(string $name): void;

    public function endSection(): void;

    public function section(string $name, string $default = ''): string;

    public function hasSection(string $name): bool;

    public function setSection(string $name, string $content): void;

    public function appendSection(string $name, string $content): void;

    public function prependSection(string $name, string $content): void;

    /**
     * @param array<string, mixed> $data
     */
    public function component(
        string|ComponentInterface $component,
        array $data = [],
        ?callable $content = null
    ): string;

    public function startSlot(string $name): void;

    /**
     * @param array<string, scalar|bool|null> $attributes
     */
    public function endSlot(array $attributes = []): void;

    /**
     * @param array<string, scalar|bool|null> $attributes
     */
    public function setSlot(
        string $name,
        string $content,
        array $attributes = []
    ): void;

    public function slot(
        string $name,
        string $default = ''
    ): Slot;

    public function escape(
        mixed $value,
        int $flags = ENT_QUOTES | ENT_SUBSTITUTE
    ): string;

    public function raw(mixed $value): string;

    public function helpers(): ViewHelpers;
}