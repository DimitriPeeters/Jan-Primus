<?php

declare(strict_types=1);

namespace AEFS\Core\Http;

use InvalidArgumentException;

final class UploadedFile
{
    public function __construct(
        private readonly string $tmpName,
        private readonly string $originalName,
        private readonly string $mimeType,
        private readonly int $size,
        private readonly int $error
    ) {
    }

    public static function fromArray(array $file): self
    {
        return new self(
            tmpName: (string) ($file['tmp_name'] ?? ''),
            originalName: (string) ($file['name'] ?? ''),
            mimeType: (string) ($file['type'] ?? 'application/octet-stream'),
            size: (int) ($file['size'] ?? 0),
            error: (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE)
        );
    }

    public function originalName(): string
    {
        return $this->originalName;
    }

    public function clientExtension(): string
    {
        return strtolower(
            pathinfo($this->originalName, PATHINFO_EXTENSION)
        );
    }

    public function filename(): string
    {
        return pathinfo(
            $this->originalName,
            PATHINFO_FILENAME
        );
    }

    public function mimeType(): string
    {
        return $this->mimeType;
    }

    public function size(): int
    {
        return $this->size;
    }

    public function error(): int
    {
        return $this->error;
    }

    public function tempPath(): string
    {
        return $this->tmpName;
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK
            && is_uploaded_file($this->tmpName);
    }

    public function move(
        string $directory,
        ?string $filename = null
    ): string {
        if (!$this->isValid()) {
            throw new InvalidArgumentException(
                'Uploaded file is not valid.'
            );
        }

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename ??= sprintf(
            '%s.%s',
            bin2hex(random_bytes(16)),
            $this->clientExtension()
        );

        $destination = rtrim(
            $directory,
            DIRECTORY_SEPARATOR
        ) . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($this->tmpName, $destination)) {
            throw new InvalidArgumentException(
                'Unable to move uploaded file.'
            );
        }

        return $destination;
    }

    public function contents(): string
    {
        return file_get_contents($this->tmpName) ?: '';
    }

    public function image(): bool
    {
        return str_starts_with(
            strtolower($this->mimeType),
            'image/'
        );
    }

    public function extension(): string
    {
        return strtolower(
            pathinfo($this->originalName, PATHINFO_EXTENSION)
        );
    }
}