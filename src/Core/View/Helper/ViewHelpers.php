<?php

declare(strict_types=1);

namespace AEFS\Core\View\Helper;

final class ViewHelpers
{
    public function __construct(
        public readonly AssetHelper $asset,
        public readonly UrlHelper $url,
        public readonly CsrfHelper $csrf,
        public readonly FlashHelper $flash,
        public readonly FormHelper $form,
        public readonly ErrorBag $errors,
        public readonly ErrorRenderer $errorRenderer,
        public readonly OldInputHelper $old
    ) {
    }
}