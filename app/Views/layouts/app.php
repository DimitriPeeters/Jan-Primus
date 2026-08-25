<?php

$this->extend('layouts.base', [
    'title' => $title ?? 'Ledenbeheer',
    'bodyClass' => 'app-layout',
]);
?>

<?php $this->startSection('body'); ?>

<div class="app">
    <?= $this->partial('partials.sidebar') ?>

    <div class="app__main">
        <?= $this->partial(
            'partials.header',
            [
                'title' => $title ?? 'Ledenbeheer',
            ]
        ) ?>

        <main class="app__content">
            <?= $this->partial('partials.flash') ?>
            <?= $this->partial('partials.errors') ?>

            <?= $this->section('content') ?>
        </main>

        <?= $this->partial('partials.footer') ?>
    </div>
</div>

<?php $this->endSection(); ?>
