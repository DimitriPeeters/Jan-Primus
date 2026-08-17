<?php

declare(strict_types=1);

namespace AEFS\Core\View;

use LogicException;

final class ViewContext
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * @var array<string, string>
     */
    private array $sections = [];

    /**
     * @var list<string>
     */
    private array $sectionStack = [];

    private ?string $layout = null;

    /**
     * @var array<string, mixed>
     */
    private array $layoutData = [];

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        return $this->data;
    }

    public function get(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->data[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists(
            $key,
            $this->data
        );
    }

    public function set(
        string $key,
        mixed $value
    ): void {
        $this->data[$key] = $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function merge(array $data): void
    {
        $this->data = array_replace(
            $this->data,
            $data
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function extend(
        string $layout,
        array $data = []
    ): void {
        $layout = trim($layout);

        if ($layout === '') {
            throw new LogicException(
                'Layoutnaam mag niet leeg zijn.'
            );
        }

        $this->layout = $layout;
        $this->layoutData = $data;
    }

    public function layout(): ?string
    {
        return $this->layout;
    }

    /**
     * @return array<string, mixed>
     */
    public function layoutData(): array
    {
        return $this->layoutData;
    }

    /**
     * @return array{
     *     layout: string,
     *     data: array<string, mixed>
     * }|null
     */
    public function consumeLayout(): ?array
    {
        if ($this->layout === null) {
            return null;
        }

        $layout = [
            'layout' => $this->layout,
            'data' => $this->layoutData,
        ];

        $this->layout = null;
        $this->layoutData = [];

        return $layout;
    }

    public function startSection(string $name): void
    {
        $name = trim($name);

        if ($name === '') {
            throw new LogicException(
                'Sectienaam mag niet leeg zijn.'
            );
        }

        if (in_array($name, $this->sectionStack, true)) {
            throw new LogicException(
                sprintf(
                    'Sectie [%s] is reeds geopend.',
                    $name
                )
            );
        }

        $this->sectionStack[] = $name;

        ob_start();
    }

    public function endSection(): void
    {
        if ($this->sectionStack === []) {
            throw new LogicException(
                'Er is geen geopende sectie om af te sluiten.'
            );
        }

        $content = ob_get_clean();

        if ($content === false) {
            throw new LogicException(
                'De sectiebuffer kon niet worden afgesloten.'
            );
        }

        $name = array_pop($this->sectionStack);

        if ($name === null) {
            throw new LogicException(
                'De sectienaam kon niet worden bepaald.'
            );
        }

        $this->sections[$name] = $content;
    }

    public function setSection(
        string $name,
        string $content
    ): void {
        $name = trim($name);

        if ($name === '') {
            throw new LogicException(
                'Sectienaam mag niet leeg zijn.'
            );
        }

        $this->sections[$name] = $content;
    }

    public function appendSection(
        string $name,
        string $content
    ): void {
        $name = trim($name);

        if ($name === '') {
            throw new LogicException(
                'Sectienaam mag niet leeg zijn.'
            );
        }

        $this->sections[$name] = (
            $this->sections[$name] ?? ''
        ) . $content;
    }

    public function prependSection(
        string $name,
        string $content
    ): void {
        $name = trim($name);

        if ($name === '') {
            throw new LogicException(
                'Sectienaam mag niet leeg zijn.'
            );
        }

        $this->sections[$name] = $content . (
            $this->sections[$name] ?? ''
        );
    }

    public function section(
        string $name,
        string $default = ''
    ): string {
        return $this->sections[$name] ?? $default;
    }

    public function hasSection(string $name): bool
    {
        return array_key_exists(
            $name,
            $this->sections
        );
    }

    /**
     * @return array<string, string>
     */
    public function sections(): array
    {
        return $this->sections;
    }

    public function hasOpenSections(): bool
    {
        return $this->sectionStack !== [];
    }

    /**
     * @return list<string>
     */
    public function openSections(): array
    {
        return $this->sectionStack;
    }
}