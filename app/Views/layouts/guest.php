<?php

$this->extend('layouts.base', [
    'title' => $title ?? 'Aanmelden',
    'bodyClass' => 'guest-layout',
]);
?>
<?php $this->startSection('body'); ?>
<main class="guest-layout__content">
    <?= $this->partial('partials.flash') ?>
    <?= $this->partial('partials.errors') ?>
    <?= $this->section('content') ?>
</main>
<?php $this->endSection(); ?>
