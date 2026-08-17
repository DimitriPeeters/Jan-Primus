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
    <title>Ongeldige aanvraag</title>
</head>
<body>
    <main>
        <h1>400</h1>
        <h2>Ongeldige aanvraag</h2>

        <p>
            <?= $this->escape(
                $message ?? 'De aanvraag kon niet worden verwerkt.'
            ) ?>
        </p>
    </main>
</body>
</html>