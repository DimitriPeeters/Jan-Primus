<?php

declare(strict_types=1);

namespace AEFS\Core\View;

use AEFS\Core\Http\Response;
use AEFS\Core\View\Component\AnonymousComponent;
use AEFS\Core\View\Component\ComponentContext;
use AEFS\Core\View\Component\ComponentInterface;
use AEFS\Core\View\Component\Slot;
use AEFS\Core\View\Composer\ViewComposerRegistry;
use AEFS\Core\View\Exception\InvalidViewDataException;
use AEFS\Core\View\Exception\ViewRenderingException;
use AEFS\Core\View\Helper\ViewHelpers;
use InvalidArgumentException;
use LogicException;
use Throwable;

final class ViewEngine implements ViewEngineInterface
{
    private const MAX_LAYOUT_DEPTH = 20;

    /**
     * @var array<string, mixed>
     */
    private array $sharedData = [];

    private ?ViewContext $activeContext = null;

    /**
     * @var list<ComponentContext>
     */
    private array $componentStack = [];

    public function __construct(
        private readonly ViewFinder $finder,
        private readonly ViewComposerRegistry $composers,
        private readonly ViewHelpers $helpers,
        private readonly ViewDataValidator $dataValidator
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(
        string $view,
        array $data = []
    ): string {
        $view = $this->normalizeViewName($view);

        $this->dataValidator->validate($data);

        $data = array_replace(
            $this->sharedData,
            $data,
            [
                'view' => $this,
                'helpers' => $this->helpers,
            ]
        );

        $data = $this->compose(
            $view,
            $data
        );

        $context = new ViewContext($data);
        $previousContext = $this->activeContext;

        $this->activeContext = $context;

        try {
            return $this->renderInheritanceChain(
                $view,
                $context
            );
        } finally {
            $this->activeContext = $previousContext;
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function response(
        string $view,
        array $data = [],
        int $status = 200,
        array $headers = []
    ): Response {
        if ($status < 100 || $status > 599) {
            throw new InvalidArgumentException(
                sprintf(
                    'Ongeldige HTTP-statuscode [%d].',
                    $status
                )
            );
        }

        $headers = array_replace(
            [
                'Content-Type' => 'text/html; charset=UTF-8',
            ],
            $headers
        );

        return new Response(
            $this->render($view, $data),
            $status,
            $headers
        );
    }

    public function share(
        string $key,
        mixed $value
    ): void {
        $key = trim($key);

        $this->dataValidator->validateKey($key);

        if (in_array($key, ['view', 'helpers'], true)) {
            throw InvalidViewDataException::reservedVariable($key);
        }

        $this->sharedData[$key] = $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function shareMany(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->share($key, $value);
        }
    }

    public function exists(string $view): bool
    {
        return $this->finder->exists($view);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function include(
        string $view,
        array $data = []
    ): string {
        $view = $this->normalizeViewName($view);
        $context = $this->context();

        $this->dataValidator->validate($data);

        $viewData = array_replace(
            $context->data(),
            $data,
            [
                'view' => $this,
                'helpers' => $this->helpers,
            ]
        );

        $viewData = $this->compose(
            $view,
            $viewData
        );

        return $this->renderNamedView(
            $view,
            $viewData
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function partial(
        string $view,
        array $data = []
    ): string {
        return $this->include(
            $view,
            $data
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function extend(
        string $layout,
        array $data = []
    ): void {
        $this->dataValidator->validate($data);

        $this->context()->extend(
            $this->normalizeViewName($layout),
            $data
        );
    }

    public function startSection(string $name): void
    {
        $this->context()->startSection($name);
    }

    public function endSection(): void
    {
        $this->context()->endSection();
    }

    public function section(
        string $name,
        string $default = ''
    ): string {
        return $this->context()->section(
            $name,
            $default
        );
    }

    public function hasSection(string $name): bool
    {
        return $this->context()->hasSection($name);
    }

    public function setSection(
        string $name,
        string $content
    ): void {
        $this->context()->setSection(
            $name,
            $content
        );
    }

    public function appendSection(
        string $name,
        string $content
    ): void {
        $this->context()->appendSection(
            $name,
            $content
        );
    }

    public function prependSection(
        string $name,
        string $content
    ): void {
        $this->context()->prependSection(
            $name,
            $content
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function component(
        string|ComponentInterface $component,
        array $data = [],
        ?callable $content = null
    ): string {
        $this->dataValidator->validate($data);

        $instance = $this->resolveComponent(
            $component,
            $data
        );

        $componentContext = new ComponentContext(
            $instance
        );

        $this->componentStack[] = $componentContext;

        try {
            if ($content !== null) {
                $defaultContent = $this->capture(
                    static function () use ($content): void {
                        $content();
                    }
                );

                if ($componentContext->hasOpenSlot()) {
                    throw new LogicException(
                        sprintf(
                            'Slot [%s] werd niet afgesloten.',
                            $componentContext->activeSlot()
                        )
                    );
                }

                $componentContext->setDefaultContent(
                    $defaultContent
                );
            }

            $componentData = array_replace(
                $this->sharedData,
                $instance->data(),
                $data,
                [
                    'slot' => $componentContext->defaultSlot(),
                    'slots' => $componentContext->slots(),
                    'component' => $instance,
                    'view' => $this,
                    'helpers' => $this->helpers,
                ]
            );

            $componentData = $this->compose(
                $instance->view(),
                $componentData
            );

            return $this->renderNamedView(
                $instance->view(),
                $componentData
            );
        } finally {
            array_pop($this->componentStack);
        }
    }

    public function startSlot(string $name): void
    {
        $this->componentContext()->startSlot($name);
    }

    /**
     * @param array<string, scalar|bool|null> $attributes
     */
    public function endSlot(array $attributes = []): void
    {
        $this->componentContext()->endSlot($attributes);
    }

    /**
     * @param array<string, scalar|bool|null> $attributes
     */
    public function setSlot(
        string $name,
        string $content,
        array $attributes = []
    ): void {
        $this->componentContext()->setSlot(
            $name,
            $content,
            $attributes
        );
    }

    public function slot(
        string $name,
        string $default = ''
    ): Slot {
        return $this->componentContext()
            ->slots()
            ->get(
                $name,
                new Slot($default)
            )
            ?? new Slot($default);
    }

    public function escape(
        mixed $value,
        int $flags = ENT_QUOTES | ENT_SUBSTITUTE
    ): string {
        return htmlspecialchars(
            (string) $value,
            $flags,
            'UTF-8'
        );
    }

    public function raw(mixed $value): string
    {
        return (string) $value;
    }

    public function helpers(): ViewHelpers
    {
        return $this->helpers;
    }

    private function renderInheritanceChain(
        string $view,
        ViewContext $context
    ): string {
        $currentView = $view;
        $renderedContent = '';
        $visitedViews = [];
        $depth = 0;

        while (true) {
            if ($depth >= self::MAX_LAYOUT_DEPTH) {
                throw new LogicException(
                    sprintf(
                        'Maximale layoutdiepte van %d werd overschreden.',
                        self::MAX_LAYOUT_DEPTH
                    )
                );
            }

            if (in_array($currentView, $visitedViews, true)) {
                $visitedViews[] = $currentView;

                throw new LogicException(
                    sprintf(
                        'Cyclische view inheritance gedetecteerd: %s.',
                        implode(' -> ', $visitedViews)
                    )
                );
            }

            $visitedViews[] = $currentView;

            $viewData = $this->compose(
                $currentView,
                $context->data()
            );

            $context->merge($viewData);

            $renderedContent = $this->renderNamedView(
                $currentView,
                $context->data()
            );

            $this->assertNoOpenSections($context);

            $layoutDefinition = $context->consumeLayout();

            if ($layoutDefinition === null) {
                return $renderedContent;
            }

            if (!$context->hasSection('content')) {
                $context->setSection(
                    'content',
                    $renderedContent
                );
            }

            $context->merge(
                $layoutDefinition['data']
            );

            $currentView = $this->normalizeViewName(
                $layoutDefinition['layout']
            );

            $depth++;
        }
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function compose(
        string $view,
        array $data
    ): array {
        $composedData = $this->composers->compose(
            $view,
            $data
        );

        $this->dataValidator->validate($composedData);

        return array_replace(
            $composedData,
            [
                'view' => $this,
                'helpers' => $this->helpers,
            ]
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderNamedView(
        string $view,
        array $data
    ): string {
        $file = $this->finder->find($view);

        try {
            return $this->renderFile(
                $file,
                $data
            );
        } catch (ViewRenderingException $exception) {
            throw $exception;
        } catch (Throwable $throwable) {
            throw new ViewRenderingException(
                $view,
                $file,
                $throwable
            );
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderFile(
        string $file,
        array $data
    ): string {
        $renderer = function (
            string $__file,
            array $__data
        ): string {
            extract(
                $__data,
                EXTR_SKIP
            );

            $bufferLevel = ob_get_level();

            ob_start();

            try {
                require $__file;

                $content = ob_get_clean();

                if ($content === false) {
                    throw new LogicException(
                        sprintf(
                            'De viewbuffer voor [%s] kon niet worden gelezen.',
                            $__file
                        )
                    );
                }

                return $content;
            } catch (Throwable $throwable) {
                while (ob_get_level() > $bufferLevel) {
                    ob_end_clean();
                }

                throw $throwable;
            }
        };

        return $renderer->call(
            $this,
            $file,
            $data
        );
    }

    private function capture(callable $callback): string
    {
        $bufferLevel = ob_get_level();

        ob_start();

        try {
            $callback();

            $content = ob_get_clean();

            if ($content === false) {
                throw new LogicException(
                    'De uitvoerbuffer kon niet worden gelezen.'
                );
            }

            return $content;
        } catch (Throwable $throwable) {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }

            throw $throwable;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveComponent(
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

    private function context(): ViewContext
    {
        if ($this->activeContext === null) {
            throw new LogicException(
                'Er is momenteel geen actieve viewcontext.'
            );
        }

        return $this->activeContext;
    }

    private function componentContext(): ComponentContext
    {
        $context = end($this->componentStack);

        if (!$context instanceof ComponentContext) {
            throw new LogicException(
                'Er is momenteel geen actieve componentcontext.'
            );
        }

        return $context;
    }

    private function assertNoOpenSections(
        ViewContext $context
    ): void {
        if (!$context->hasOpenSections()) {
            return;
        }

        throw new LogicException(
            sprintf(
                'Niet afgesloten viewsecties: %s',
                implode(
                    ', ',
                    $context->openSections()
                )
            )
        );
    }

    private function normalizeViewName(string $view): string
    {
        $view = trim($view);

        if ($view === '') {
            throw new InvalidArgumentException(
                'Viewnaam mag niet leeg zijn.'
            );
        }

        return $view;
    }
}