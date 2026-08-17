<?php

declare(strict_types=1);

namespace AEFS\Core\View\Component;

use AEFS\Core\View\ViewEngineInterface;
use InvalidArgumentException;

final class ComponentRenderer
{
    public function __construct(
        private readonly ViewEngineInterface $view
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, Slot|string> $slots
     */
    public function render(
        string|ComponentInterface $component,
        array $data = [],
        string $defaultSlot = '',
        array $slots = []
    ): string {
        $instance = $this->resolve($component, $data);
        $slotBag = new SlotBag($slots);

        $componentData = array_replace(
            $instance->data(),
            [
                'slot' => new Slot($defaultSlot),
                'slots' => $slotBag,
                'component' => $instance,
            ]
        );

        return $this->view->render(
            $instance->view(),
            $componentData
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolve(
        string|ComponentInterface $component,
        array $data
    ): ComponentInterface {
        if ($component instanceof ComponentInterface) {
            return $component;
        }

        $component = trim($component);

        if ($component === '') {
            throw new InvalidArgumentException(
                'Componentnaam mag niet leeg zijn.'
            );
        }

        return new AnonymousComponent(
            'components.' . $component,
            $data
        );
    }
}