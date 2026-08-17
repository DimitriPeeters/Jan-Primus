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
    <title>Ongeldige gegevens</title>
</head>
<body>
    <main>
        <h1>422</h1>
        <h2>Ongeldige gegevens</h2>

        <p>
            <?= $this->escape(
                $message ?? 'De ingevoerde gegevens zijn niet geldig.'
            ) ?>
        </p>
    </main>
</body>
</html>