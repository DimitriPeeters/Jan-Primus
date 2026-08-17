<?php

declare(strict_types=1);

namespace App\Services;

use AEFS\Core\Config;
use RuntimeException;

final class EncryptionService
{
    private const CIPHER = 'AES-256-CBC';

    private const PREFIX = 'enc:v1:';

    private readonly string $key;

    private readonly int $ivLength;

    public function __construct(?Config $config = null)
    {
        $config ??= new Config(
            dirname(__DIR__, 2)
                . DIRECTORY_SEPARATOR
                . 'config'
        );

        $appKey = trim(
            (string) $config->get('app.app_key', '')
        );

        if ($appKey === '') {
            throw new RuntimeException(
                'Geen app_key gevonden in de applicatieconfiguratie.'
            );
        }

        $ivLength = openssl_cipher_iv_length(
            self::CIPHER
        );

        if ($ivLength === false || $ivLength <= 0) {
            throw new RuntimeException(
                'De ingestelde encryptiemethode wordt niet ondersteund.'
            );
        }

        $this->key = hash(
            'sha256',
            $appKey,
            true
        );

        $this->ivLength = $ivLength;
    }

    public function encrypt(?string $value): ?string
    {
        $value = $this->normalizeValue($value);

        if ($value === null) {
            return null;
        }

        if ($this->isEncrypted($value)) {
            return $value;
        }

        $iv = random_bytes(
            $this->ivLength
        );

        $encrypted = openssl_encrypt(
            $value,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new RuntimeException(
                'Encryptie mislukt.'
            );
        }

        return self::PREFIX . base64_encode(
            $iv . $encrypted
        );
    }

    public function decrypt(?string $value): ?string
    {
        $value = $this->normalizeValue($value);

        if ($value === null) {
            return null;
        }

        if (str_starts_with($value, self::PREFIX)) {
            return $this->decryptPrefixedValue($value);
        }

        /*
         * Ondersteuning voor waarden die vóór de invoering van
         * de prefix door deze applicatie werden versleuteld.
         *
         * Gewone legacywaarden zoals IBAN- en rijksregisternummers
         * blijven ongewijzigd wanneer zij niet aantoonbaar een geldig
         * versleuteld gegevensblok vormen.
         */
        $legacyPlain = $this->decryptLegacyValue($value);

        return $legacyPlain ?? $value;
    }

    public function isEncrypted(?string $value): bool
    {
        if ($value === null) {
            return false;
        }

        return str_starts_with(
            trim($value),
            self::PREFIX
        );
    }

    public function isUndecryptableLegacyValue(?string $value): bool
    {
        $value = $this->normalizeValue($value);

        if (
            $value === null
            || str_starts_with($value, self::PREFIX)
            || !$this->looksLikeBase64($value)
        ) {
            return false;
        }

        return $this->decryptLegacyValue($value) === null;
    }

    /**
     * @param array<string, mixed> $data
     * @param string[] $velden
     *
     * @return array<string, mixed>
     */
    public function encryptArray(
        array $data,
        array $velden
    ): array {
        foreach ($velden as $veld) {
            if (!array_key_exists($veld, $data)) {
                continue;
            }

            $data[$veld] = $this->encrypt(
                $this->stringOrNull($data[$veld])
            );
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @param string[] $velden
     *
     * @return array<string, mixed>
     */
    public function decryptArray(
        array $data,
        array $velden
    ): array {
        foreach ($velden as $veld) {
            if (!array_key_exists($veld, $data)) {
                continue;
            }

            $data[$veld] = $this->decrypt(
                $this->stringOrNull($data[$veld])
            );
        }

        return $data;
    }

    private function decryptPrefixedValue(string $value): string
    {
        $payload = substr(
            $value,
            strlen(self::PREFIX)
        );

        $binary = $this->decodePayload(
            $payload
        );

        if ($binary === null) {
            throw new RuntimeException(
                'De versleutelde waarde heeft een ongeldig formaat.'
            );
        }

        $plain = $this->decryptBinary(
            $binary
        );

        if ($plain === null) {
            throw new RuntimeException(
                'Ontsleuteling mislukt. Controleer de app_key.'
            );
        }

        return $plain;
    }

    private function decryptLegacyValue(string $value): ?string
    {
        if (!$this->looksLikeBase64($value)) {
            return null;
        }

        $binary = $this->decodePayload(
            $value
        );

        if ($binary === null) {
            return null;
        }

        return $this->decryptBinary(
            $binary
        );
    }

    private function decryptBinary(string $binary): ?string
    {
        if (strlen($binary) <= $this->ivLength) {
            return null;
        }

        $iv = substr(
            $binary,
            0,
            $this->ivLength
        );

        if (strlen($iv) !== $this->ivLength) {
            return null;
        }

        $encrypted = substr(
            $binary,
            $this->ivLength
        );

        if ($encrypted === '') {
            return null;
        }

        $plain = openssl_decrypt(
            $encrypted,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($plain === false) {
            return null;
        }

        return $plain;
    }

    private function decodePayload(string $payload): ?string
    {
        $binary = base64_decode(
            $payload,
            true
        );

        if ($binary === false) {
            return null;
        }

        if (strlen($binary) <= $this->ivLength) {
            return null;
        }

        return $binary;
    }

    private function looksLikeBase64(string $value): bool
    {
        $length = strlen($value);

        if ($length === 0 || $length % 4 !== 0) {
            return false;
        }

        if (
            preg_match(
                '/^[A-Za-z0-9+\/]+={0,2}$/',
                $value
            ) !== 1
        ) {
            return false;
        }

        $decoded = base64_decode(
            $value,
            true
        );

        if ($decoded === false) {
            return false;
        }

        if (strlen($decoded) <= $this->ivLength) {
            return false;
        }

        return hash_equals(
            rtrim($value, '='),
            rtrim(base64_encode($decoded), '=')
        );
    }

    private function normalizeValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }
}
