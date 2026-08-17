<?php

declare(strict_types=1);

namespace AEFS\Core\Http;

final class FileBag
{
    /**
     * @var array<string, UploadedFile|array>
     */
    private array $files = [];

    /**
     * @param array<string, mixed> $files
     */
    public function __construct(array $files = [])
    {
        foreach ($files as $key => $file) {
            $this->files[$key] = $this->convert($file);
        }
    }

    /**
     * @return array<string, UploadedFile|array>
     */
    public function all(): array
    {
        return $this->files;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->files);
    }

    public function get(string $key): UploadedFile|array|null
    {
        return $this->files[$key] ?? null;
    }

    public function set(string $key, UploadedFile|array $file): void
    {
        $this->files[$key] = $file;
    }

    public function remove(string $key): void
    {
        unset($this->files[$key]);
    }

    public function count(): int
    {
        return count($this->files);
    }

    /**
     * @param mixed $file
     */
    private function convert(mixed $file): UploadedFile|array|null
    {
        if (!is_array($file)) {
            return null;
        }

        if (isset($file['tmp_name'])) {
            return UploadedFile::fromArray($file);
        }

        $converted = [];

        foreach ($file as $key => $value) {
            $converted[$key] = $this->convert($value);
        }

        return $converted;
    }
}