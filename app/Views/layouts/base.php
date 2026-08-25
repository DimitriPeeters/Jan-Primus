<?php

use AEFS\Core\View\Helper\ViewHelpers;

/** @var ViewHelpers $helpers */
/** @var string|null $title */
/** @var string|null $applicationName */

$pageTitle = trim((string) ($title ?? ''));
$applicationName = $applicationName ?? 'Ledenbeheer';
$pageTitle = $pageTitle === '' ? $applicationName : $pageTitle . ' | ' . $applicationName;
?>
<!DOCTYPE html>
<html lang="nl-BE">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->escape($pageTitle) ?></title>
    <link
        rel="icon"
        type="image/png"
        sizes="256x256"
        href="<?= $this->escape(
            $helpers->asset->url('images/favicon.png')
        ) ?>"
    >
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <?php foreach (['theme','layout','sidebar','buttons','forms','cards','tables','badges','alerts','utilities','icons','dashboard'] as $stylesheet): ?>
        <?= $helpers->asset->css('css/' . $stylesheet . '.css') ?>
    <?php endforeach; ?>
    <?= $this->section('styles') ?>
</head>
<body class="<?= $this->escape((string) ($bodyClass ?? '')) ?>">
    <?= $this->section('body') ?>
    <?= $this->section('scripts') ?>
</body>
</html>
