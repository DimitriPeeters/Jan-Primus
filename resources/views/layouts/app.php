<?php

declare(strict_types=1);

/**
 * Verwachte variabelen:
 *
 * $title
 * $content
 */

use AEFS\Core\Url;

$title ??= 'AEFS';

?>
<!DOCTYPE html>

<html lang="nl">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title><?= htmlspecialchars($title) ?> | AEFS</title>

    <link
        rel="icon"
        href="<?= Url::asset('branding/favicon.ico') ?>"
    >

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="<?= Url::asset('css/theme.css') ?>"
    >

    <link
        rel="stylesheet"
        href="<?= Url::asset('css/layout.css') ?>"
    >

    <link
        rel="stylesheet"
        href="<?= Url::asset('css/sidebar.css') ?>"
    >

    <link
        rel="stylesheet"
        href="<?= Url::asset('css/buttons.css') ?>"
    >

    <link
        rel="stylesheet"
        href="<?= Url::asset('css/forms.css') ?>"
    >

    <link
        rel="stylesheet"
        href="<?= Url::asset('css/cards.css') ?>"
    >

    <link
        rel="stylesheet"
        href="<?= Url::asset('css/tables.css') ?>"
    >

    <link
        rel="stylesheet"
        href="<?= Url::asset('css/badges.css') ?>"
    >

    <link
        rel="stylesheet"
        href="<?= Url::asset('css/alerts.css') ?>"
    >

    <link
        rel="stylesheet"
        href="<?= Url::asset('css/utilities.css') ?>"
    >

</head>

<body>

<div class="wrapper">

    <?php require __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="main">

        <?php require __DIR__ . '/../partials/header.php'; ?>

        <main class="content">

            <?php require __DIR__ . '/../partials/flash.php'; ?>

            <?= $content ?>

        </main>

        <?php require __DIR__ . '/../partials/footer.php'; ?>

    </div>

</div>

</body>

<link
    rel="stylesheet"
    href="<?= Url::asset('css/icons.css') ?>"
>

</html>