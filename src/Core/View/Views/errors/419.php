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
    <title>Sessie verlopen</title>
</head>
<body>
    <main>
        <h1>419</h1>
        <h2>Sessie verlopen</h2>

        <p>
            <?= $this->escape(
                $message
                    ?? 'De beveiligingstoken is verlopen. Vernieuw de pagina en probeer opnieuw.'
            ) ?>
        </p>
    </main>
</body>
</html>