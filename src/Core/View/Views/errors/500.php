<?php


/** @var string|null $message */
/** @var bool|null $debug */
/** @var Throwable|null $exception */
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>Interne serverfout</title>
</head>
<body>
    <main>
        <h1>500</h1>
        <h2>Interne serverfout</h2>

        <p>
            <?= $this->escape(
                $message
                    ?? 'Er is een onverwachte fout opgetreden.'
            ) ?>
        </p>

        <?php if (($debug ?? false) && isset($exception)): ?>
            <hr>

            <h3><?= $this->escape($exception::class) ?></h3>

            <p>
                <?= $this->escape($exception->getMessage()) ?>
            </p>

            <pre><?= $this->escape($exception->getTraceAsString()) ?></pre>
        <?php endif; ?>
    </main>
</body>
</html>