<?php

declare(strict_types=1);

namespace AEFS\Core;

final class View
{
    /**
     * @param array<string,mixed> $data
     */
    public static function render(string $view, array $data = []): void
    {
        $viewFile = dirname(__DIR__, 2)
            . '/resources/views/'
            . str_replace('.', '/', $view)
            . '.php';

        if (!is_file($viewFile)) {
            Response::notFound();
        }

        extract($data, EXTR_SKIP);

        ob_start();

        require $viewFile;

        $content = ob_get_clean();

        if ($content === false) {
            $content = '';
        }

        $title ??= 'AEFS';

        require dirname(__DIR__, 2)
            . '/resources/views/layouts/app.php';
    }
}