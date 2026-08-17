<?php

declare(strict_types=1);

namespace AEFS\Core\View\Composer;

use Closure;
use InvalidArgumentException;

final readonly class ComposerDefinition
{
    /**
     * @param list<string> $views
     */
    public function __construct(
        public array $views,
        public ViewComposerInterface|Closure $composer
    ) {
        if ($this->views === []) {
            throw new InvalidArgumentException(
                'Een composerdefinitie moet minstens één view bevatten.'
            );
        }

        foreach ($this->views as $view) {
            if (trim($view) === '') {
                throw new InvalidArgumentException(
                    'Composer-viewnamen mogen niet leeg zijn.'
                );
            }
        }
    }
}