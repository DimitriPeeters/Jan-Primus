# Installatie in de gedeelde One.com-database

De actieve WordPress-website en Ledenbeheer gebruiken dezelfde database.
WordPress-tabellen zijn productiegegevens en vallen volledig buiten het beheer
van Ledenbeheer.

## Veilige volgorde

1. Maak een volledige databaseback-up via One.com.
2. Vul de genegeerde `config/local/database.php` in met de One.com-gegevens.
3. Zoek de echte WordPress-tabelprefix op in `wp-config.php`.
4. Voer de alleen-lezen controle uit:

   ```text
   php bin/check-shared-database.php --wordpress-prefix=wp_
   ```

5. Stop wanneer de controle geen WordPress-tabellen vindt of een tabelconflict
   meldt.
6. Importeer pas na een geslaagde controle
   `database/install/onecom-shared-database.sql` via phpMyAdmin.
7. Voer de controle na de import opnieuw uit. Exitcode `2` is dan verwacht omdat
   de Ledenbeheer-tabellen nu bestaan; vergelijk de gemelde lijst met het
   installatieschema.

## Veiligheidskenmerken

- Het installatieschema maakt uitsluitend bekende Ledenbeheer-tabellen aan.
- Elk lid is via een verplichte unieke relatie aan exact één gebruikersaccount gekoppeld.
- Groepen, IBAN, lidgeld en vergoedingsvelden maken geen deel uit van het schema.
- Het bevat geen `DROP`, `TRUNCATE`, `CREATE DATABASE` of `USE`.
- WordPress-tabellen worden niet gelezen of gewijzigd door het schema.
- Basisinstellingen worden alleen toegevoegd wanneer hun sleutel nog ontbreekt.
- Het standaard shifttype `Steward` wordt idempotent toegevoegd.
- Uitgevoerde installaties worden geregistreerd in `ledenbeheer_migraties`.

Gebruik `database/database.sql` en de historische AEFS-cutoverbuilder nooit op
de gedeelde Jan Primus-database.
