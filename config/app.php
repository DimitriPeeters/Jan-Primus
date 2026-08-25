<?php

declare(strict_types=1);

$environmentValue = getenv('APP_ENV');
$baseUrlValue = getenv('APP_URL');
$appKeyValue = getenv('APP_KEY');

$configuration = [

    /*
    |--------------------------------------------------------------------------
    | Application
    |--------------------------------------------------------------------------
    */

    'name' => 'Ledenbeheer',

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | Leeg laten = automatisch detecteren.
    | Op one.com kan hier later bijvoorbeeld
    | https://leden.aefs.be ingevuld worden.
    |
    */

    'base_url' => $baseUrlValue !== false
        ? trim($baseUrlValue)
        : '',

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    */

    'environment' => $environmentValue !== false
        ? trim($environmentValue)
        : 'development',

    /*
    |--------------------------------------------------------------------------
    | Timezone
    |--------------------------------------------------------------------------
    |
    | Alle datums en tijdstippen binnen AEFS worden geïnterpreteerd volgens
    | deze tijdzone. Dit voorkomt datumverschillen tussen de lokale omgeving,
    | UTC en de uiteindelijke productieserver.
    |
    */

    'timezone' => 'Europe/Brussels',

    /*
    |--------------------------------------------------------------------------
    | Application Key
    |--------------------------------------------------------------------------
    |
    | Wordt gebruikt voor encryptie van gevoelige gegevens zoals:
    | - rekeningnummer
    | - rijksregisternummer
    | - toekomstige API-sleutels
    |
    | Deze sleutel NOOIT wijzigen nadat de applicatie in productie is,
    | anders kunnen bestaande gegevens niet meer ontsleuteld worden.
    |
    */

    'app_key' => $appKeyValue !== false
        ? trim($appKeyValue)
        : '',

];

$localFile = __DIR__
    . DIRECTORY_SEPARATOR
    . 'local'
    . DIRECTORY_SEPARATOR
    . 'app.php';

if (is_file($localFile)) {
    $localConfiguration = require $localFile;

    if (!is_array($localConfiguration)) {
        throw new RuntimeException(
            'De lokale applicatieconfiguratie moet een array teruggeven.'
        );
    }

    $configuration = array_replace(
        $configuration,
        $localConfiguration
    );
}

return $configuration;
