<?php

declare(strict_types=1);

namespace AEFS\Core\Http;

use JsonSerializable;

final class JsonResponse extends Response
{
    /**
     * @param array<mixed>|JsonSerializable $data
     */
    public function __construct(
        array|JsonSerializable $data = [],
        int $statusCode = 200,
        array $headers = [],
        int $options = JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
    ) {
        $headers['Content-Type'] = 'application/json; charset=UTF-8';

        parent::__construct(
            json_encode($data, $options),
            $statusCode,
            $headers
        );
    }

    /**
     * @param array<mixed>|JsonSerializable $data
     */
    public function setData(
        array|JsonSerializable $data,
        int $options = JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_THROW_ON_ERROR
    ): self {
        $this->setContent(
            json_encode($data, $options)
        );

        return $this;
    }

    /**
     * @param array<mixed>|JsonSerializable $data
     */
    public static function success(
        array|JsonSerializable $data = [],
        string $message = 'OK',
        int $statusCode = 200
    ): self {
        return new self(
            [
                'success' => true,
                'message' => $message,
                'data'    => $data,
            ],
            $statusCode
        );
    }

    /**
     * @param array<mixed> $errors
     */
    public static function error(
        string $message,
        array $errors = [],
        int $statusCode = 400
    ): self {
        return new self(
            [
                'success' => false,
                'message' => $message,
                'errors'  => $errors,
            ],
            $statusCode
        );
    }
}