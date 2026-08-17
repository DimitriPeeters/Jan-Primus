<?php

$this->extend('layouts.base', [
    'title' => $title ?? 'AEFS Eventbeheer',
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
                'title' => $title ?? 'AEFS Eventbeheer',
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