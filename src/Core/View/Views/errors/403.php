<?php


/** @var string|null $message */
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >
    <title>Geen toegang</title>
</head>
<body>
    <main>
        <h1>403</h1>
        <h2>Geen toegang</h2>

        <p>
            <?= $this->escape(
                $message ?? 'Je hebt geen toegang tot deze pagina.'
            ) ?>
        </p>
    </main>
</body>
</html>