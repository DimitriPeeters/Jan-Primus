<?php

declare(strict_types=1);

namespace AEFS\Core\View;

use AEFS\Core\Container;
use AEFS\Core\Session;
use AEFS\Core\View\Composer\ViewComposerRegistry;
use AEFS\Core\View\Error\ErrorViewRenderer;
use AEFS\Core\View\Helper\AssetHelper;
use AEFS\Core\View\Helper\CsrfHelper;
use AEFS\Core\View\Helper\ErrorBag;
use AEFS\Core\View\Helper\ErrorRenderer;
use AEFS\Core\View\Helper\FlashHelper;
use AEFS\Core\View\Helper\FormHelper;
use AEFS\Core\View\Helper\MethodFieldHelper;
use AEFS\Core\View\Helper\OldInputHelper;
use AEFS\Core\View\Helper\UrlHelper;
use AEFS\Core\View\Helper\ViewHelpers;
use InvalidArgumentException;

final class ViewServiceProvider implements ViewServiceProviderInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function register(
        Container $container,
        array $config
    ): void {
        $paths = $this->resolvePaths($config);
        $namespaces = $this->resolveNamespaces($config);

        $baseUrl = $this->stringValue(
            $config,
            'base_url',
            ''
        );

        $assetPath = $this->stringValue(
            $config,
            'asset_path',
            'assets'
        );

        $assetVersion = $this->stringValue(
            $config,
            'asset_version',
            ''
        );

        $debug = $this->boolValue(
            $config,
            'debug',
            false
        );

        $container->singleton(
            ViewFinder::class,
            static function () use (
                $paths,
                $namespaces
            ): ViewFinder {
                $finder = new ViewFinder($paths);

                foreach ($namespaces as $namespace => $path) {
                    $finder->addNamespace(
                        $namespace,
                        $path
                    );
                }

                return $finder;
            }
        );

        $container->singleton(
            ViewDataValidator::class,
            static fn (): ViewDataValidator => new ViewDataValidator()
        );

        $container->singleton(
            ViewComposerRegistry::class,
            static fn (): ViewComposerRegistry => new ViewComposerRegistry()
        );

        $container->singleton(
            AssetHelper::class,
            static fn (): AssetHelper => new AssetHelper(
                $baseUrl,
                $assetPath,
                $assetVersion
            )
        );

        $container->singleton(
            UrlHelper::class,
            static fn (): UrlHelper => new UrlHelper(
                $baseUrl
            )
        );

        $container->singleton(
            CsrfHelper::class,
            static fn (
                Container $container
            ): CsrfHelper => new CsrfHelper(
                $container->get(Session::class)
            )
        );

        $container->singleton(
            FlashHelper::class,
            static fn (
                Container $container
            ): FlashHelper => new FlashHelper(
                $container->get(Session::class)
            )
        );

        $container->singleton(
            OldInputHelper::class,
            static fn (
                Container $container
            ): OldInputHelper => new OldInputHelper(
                $container->get(Session::class)
            )
        );

        $container->singleton(
            ErrorBag::class,
            static fn (
                Container $container
            ): ErrorBag => new ErrorBag(
                self::resolveErrors(
                    $container->get(Session::class)
                )
            )
        );

        $container->singleton(
            ErrorRenderer::class,
            static fn (): ErrorRenderer => new ErrorRenderer()
        );

        $container->singleton(
            MethodFieldHelper::class,
            static fn (): MethodFieldHelper => new MethodFieldHelper()
        );

        $container->singleton(
            FormHelper::class,
            static fn (
                Container $container
            ): FormHelper => new FormHelper(
                $container->get(CsrfHelper::class),
                $container->get(MethodFieldHelper::class)
            )
        );

        $container->singleton(
            ViewHelpers::class,
            static fn (
                Container $container
            ): ViewHelpers => new ViewHelpers(
                $container->get(AssetHelper::class),
                $container->get(UrlHelper::class),
                $container->get(CsrfHelper::class),
                $container->get(FlashHelper::class),
                $container->get(FormHelper::class),
                $container->get(ErrorBag::class),
                $container->get(ErrorRenderer::class),
                $container->get(OldInputHelper::class)
            )
        );

        $container->singleton(
            ViewEngine::class,
            static fn (
                Container $container
            ): ViewEngine => new ViewEngine(
                $container->get(ViewFinder::class),
                $container->get(ViewComposerRegistry::class),
                $container->get(ViewHelpers::class),
                $container->get(ViewDataValidator::class)
            )
        );

        $container->singleton(
            ViewEngineInterface::class,
            static fn (
                Container $container
            ): ViewEngineInterface => $container->get(
                ViewEngine::class
            )
        );

        $container->singleton(
            ViewManager::class,
            static fn (
                Container $container
            ): ViewManager => new ViewManager(
                $container->get(ViewEngineInterface::class),
                $container->get(ViewFinder::class),
                $container->get(ViewComposerRegistry::class)
            )
        );

        $container->singleton(
            ViewFactory::class,
            static fn (
                Container $container
            ): ViewFactory => new ViewFactory(
                $container->get(ViewEngineInterface::class)
            )
        );

        $container->singleton(
            ViewResponseFactory::class,
            static fn (
                Container $container
            ): ViewResponseFactory => new ViewResponseFactory(
                $container->get(ViewEngineInterface::class)
            )
        );

        $container->singleton(
            ErrorViewRenderer::class,
            static fn (
                Container $container
            ): ErrorViewRenderer => new ErrorViewRenderer(
                $container->get(ViewEngineInterface::class),
                $debug
            )
        );
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return list<string>
     */
    private function resolvePaths(array $config): array
    {
        $paths = $config['paths'] ?? [];

        if (!is_array($paths)) {
            throw new InvalidArgumentException(
                'De viewconfiguratie [paths] moet een array zijn.'
            );
        }

        $resolved = [];

        foreach ($paths as $path) {
            if (
                !is_string($path)
                || trim($path) === ''
            ) {
                continue;
            }

            $resolved[] = trim($path);
        }

        if ($resolved === []) {
            throw new InvalidArgumentException(
                'Er moet minstens één geldig viewpad geconfigureerd zijn.'
            );
        }

        return array_values(
            array_unique($resolved)
        );
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, string>
     */
    private function resolveNamespaces(array $config): array
    {
        $namespaces = $config['namespaces'] ?? [];

        if (!is_array($namespaces)) {
            throw new InvalidArgumentException(
                'De viewconfiguratie [namespaces] moet een array zijn.'
            );
        }

        $resolved = [];

        foreach ($namespaces as $namespace => $path) {
            if (
                !is_string($namespace)
                || trim($namespace) === ''
                || !is_string($path)
                || trim($path) === ''
            ) {
                continue;
            }

            $resolved[trim($namespace)] = trim($path);
        }

        return $resolved;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function stringValue(
        array $config,
        string $key,
        string $default
    ): string {
        $value = $config[$key] ?? $default;

        return is_string($value)
            ? trim($value)
            : $default;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function boolValue(
        array $config,
        string $key,
        bool $default
    ): bool {
        $value = $config[$key] ?? $default;

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return filter_var(
                $value,
                FILTER_VALIDATE_BOOL,
                FILTER_NULL_ON_FAILURE
            ) ?? $default;
        }

        return $default;
    }

    /**
     * @return array<string, string|list<string>>
     */
    private static function resolveErrors(
        Session $session
    ): array {
        $errors = $session->getFlash(
            '_errors',
            []
        );

        return is_array($errors)
            ? $errors
            : [];
    }
}
