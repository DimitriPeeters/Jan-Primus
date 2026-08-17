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
    <title>Pagina niet gevonden</title>
</head>
<body>
    <main>
        <h1>404</h1>
        <h2>Pagina niet gevonden</h2>

        <p>
            <?= $this->escape(
                $message ?? 'De gevraagde pagina bestaat niet.'
            ) ?>
        </p>
    </main>
</body>
</html>