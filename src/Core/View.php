<?php

declare(strict_types=1);

namespace AEFS\Core;

final class View
{
    public static function render(string $view, array $data = []): void
    {
        $bestand = dirname(__DIR__, 2)
            . '/app/Views/'
            . str_replace('.', '/', $view)
            . '.php';

        if (!file_exists($bestand)) {
            throw new \RuntimeException(
                "View '{$view}' niet gevonden."
            );
        }

        extract($data);

        ob_start();

        require $bestand;

        $content = ob_get_clean();

        require dirname(__DIR__, 2) . '/app/Views/layout.php';

        exit;
    }
}