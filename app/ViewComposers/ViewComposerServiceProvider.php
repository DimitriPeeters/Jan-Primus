<?php

declare(strict_types=1);

namespace App\ViewComposers;

use AEFS\Core\Container;
use AEFS\Core\View\ViewManager;

final class ViewComposerServiceProvider
{
    public function register(Container $container): void
    {
        $container->singleton(
            AppViewComposer::class,
            AppViewComposer::class
        );

        $container->singleton(
            NavigationViewComposer::class,
            static fn (): NavigationViewComposer => new NavigationViewComposer()
        );
    }

    public function boot(Container $container): void
    {
        $views = $container->get(ViewManager::class);

        $views->composerGlobal(
            $container->get(AppViewComposer::class)
        );

        $views->composer(
            'partials.sidebar',
            $container->get(NavigationViewComposer::class)
        );
    }
}
