<?php

declare(strict_types=1);

namespace AEFS\Core\View\Component;

use LogicException;

final class ComponentContext
{
    /**
     * @var array<string, Slot>
     */
    private array $slots = [];

    private ?string $activeSlot = null;

    private string $defaultContent = '';

    public function __construct(
        private readonly ComponentInterface $component
    ) {
    }

    public function component(): ComponentInterface
    {
        return $this->component;
    }

    public function setDefaultContent(string $content): void
    {
        $this->defaultContent = $content;
    }

    public function defaultSlot(): Slot
    {
        return new Slot($this->defaultContent);
    }

    public function startSlot(string $name): void
    {
        $name = trim($name);

        if ($name === '') {
            throw new LogicException('Slotnaam mag niet leeg zijn.');
        }

        if ($this->activeSlot !== null) {
            throw new LogicException(
                sprintf(
                    'Slot [%s] is reeds geopend.',
                    $this->activeSlot
                )
            );
        }

        $this->activeSlot = $name;

        ob_start();
    }

    /**
     * @param array<string, scalar|bool|null> $attributes
     */
    public function endSlot(array $attributes = []): void
    {
        if ($this->activeSlot === null) {
            throw new LogicException(
                'Er is geen geopende slot om af te sluiten.'
            );
        }

        $content = ob_get_clean();

        if ($content === false) {
            throw new LogicException(
                'De slotbuffer kon niet worden afgesloten.'
            );
        }

        $this->slots[$this->activeSlot] = new Slot(
            $content,
            $attributes
        );

        $this->activeSlot = null;
    }

    /**
     * @param array<string, scalar|bool|null> $attributes
     */
    public function setSlot(
        string $name,
        string $content,
        array $attributes = []
    ): void {
        $name = trim($name);

        if ($name === '') {
            throw new LogicException('Slotnaam mag niet leeg zijn.');
        }

        $this->slots[$name] = new Slot(
            $content,
            $attributes
        );
    }

    public function hasOpenSlot(): bool
    {
        return $this->activeSlot !== null;
    }

    public function activeSlot(): ?string
    {
        return $this->activeSlot;
    }

    public function slots(): SlotBag
    {
        return new SlotBag($this->slots);
    }
}