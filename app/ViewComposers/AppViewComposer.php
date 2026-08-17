<?php

declare(strict_types=1);

namespace App\ViewComposers;

use AEFS\Core\View\Composer\AbstractViewComposer;
use App\Services\SettingsService;

final class AppViewComposer extends AbstractViewComposer
{
    public function __construct(
        private readonly SettingsService $settings
    ) {
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function data(
        string $view,
        array $data
    ): array {
        return [
            'applicationName' => $this->settings->applicationName(),
            'organizationName' => $this->settings->organizationName(),
            'currentYear' => (int) date('Y'),
        ];
    }
}
