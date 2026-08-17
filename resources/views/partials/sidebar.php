<?php

declare(strict_types=1);

use AEFS\Core\Menu;
use AEFS\Core\Url;

$items = Menu::items();

?>

<aside class="sidebar">

    <div class="sidebar-logo">

        <img
            src="<?= Url::asset('branding/logo.svg') ?>"
            alt="AEFS"
            class="logo"
        >

    </div>

    <nav class="sidebar-menu">

        <?php foreach ($items as $item): ?>

            <?php

            $active = str_starts_with(
                Url::current(),
                Url::to($item['route'])
            );

            ?>

            <a
                href="<?= Url::to($item['route']) ?>"
                class="<?= $active ? 'active' : '' ?>"
            >

                <span class="icon">

                    <?= icon($item['icon']) ?>

                </span>

                <span>

                    <?= htmlspecialchars($item['title']) ?>

                </span>

            </a>

        <?php endforeach; ?>

    </nav>

    <div class="sidebar-footer">

        <form
            method="post"
            action="<?= Url::to('/logout') ?>"
        >

            <button
                class="btn btn-danger"
                style="width:100%;"
            >

                Afmelden

            </button>

        </form>

    </div>

</aside>