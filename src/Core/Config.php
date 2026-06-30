<?php

declare(strict_types=1);

namespace AEFS\Core;

final class Config
{
    private static ?Config $instance = null;

    private array $config = [];

    private function __construct()
    {
$bestand = dirname(__DIR__, 2) . '/config/aefs.php';
        if (!file_exists($bestand)) {
            throw new \RuntimeException("Configuratiebestand niet gevonden.");
        }

        $this->config = require $bestand;
    }

    public static function getInstance(): Config
    {
        if (self::$instance === null) {
            self::$instance = new Config();
        }

        return self::$instance;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);

        $waarde = $this->config;

        foreach ($keys as $deel) {

            if (!isset($waarde[$deel])) {
                return $default;
            }

            $waarde = $waarde[$deel];
        }

        return $waarde;
    }

    public function all(): array
    {
        return $this->config;
    }
}