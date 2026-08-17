# AEFS v2 deployment naar one.com

Deze procedure maakt een controleerbare alfa-deployment. Ze houdt broncode,
databasegegevens en geheimen bewust van elkaar gescheiden.

## 1. Voorwaarden bij one.com

- Maak bij voorkeur een afzonderlijk subdomein, bijvoorbeeld `leden`, zodat de
  alfa naast de bestaande website kan draaien.
- Selecteer voor dat (sub)domein PHP 8.4 of een latere, door AEFS ondersteunde
  8.4-versie.
- Activeer SFTP. SSH is alleen beschikbaar op de one.com-abonnementen die dit
  expliciet aanbieden.
- Noteer in **PHP- en database-instellingen** de databasehost, databasenaam,
  databasegebruiker en het wachtwoord. one.com laat geen externe
  databaseverbindingen toe; de applicatie gebruikt deze gegevens pas op de
  one.com-server.

Officiële one.com-documentatie:

- https://help.one.com/hc/en-us/articles/115005585569-How-do-I-change-the-PHP-version
- https://help.one.com/hc/en-us/articles/115005595645-How-to-create-a-subdomain-manually
- https://help.one.com/hc/en-us/articles/115005585689-Using-SFTP
- https://help.one.com/hc/en-us/articles/115005593685-Where-can-I-find-my-database-connection-details
- https://help.one.com/hc/en-us/articles/115005588189-How-do-I-import-a-database-to-phpMyAdmin

## 2. Back-ups en cutovervenster

Plan de eerste live-import op een afgesproken moment waarop niemand lokaal nog
gegevens wijzigt. Maak vóór de export twee afzonderlijke back-ups:

1. de huidige lokale database `aefs_v2`;
2. de bestaande one.com-database, ook wanneer die nog leeg lijkt.

Bewaar de huidige `app_key` afzonderlijk en veilig. Dezelfde sleutel is nodig
om de versleutelde nationale identificatienummers en rekeningnummers na de
migratie te kunnen lezen. Genereer geen nieuwe sleutel voor de gekopieerde
database.

`database/database.sql` is bewust een **schema-only** baseline zonder leden,
gebruikers, wachtwoordhashes, rekeningnummers of andere operationele data. De
volledige cutoverexport bevat persoonsgegevens en hoort daarom uitsluitend in
een versleutelde back-uplocatie of de genegeerde lokale map `build/`; commit of
push die export nooit.

Voer vóór de definitieve export ook alle repositorymigraties tot en met
`20260817_000007_redact_sensitive_audit_fields.sql` uit. Die laatste migratie
verwijdert uitsluitend historische kopieën van gevoelige velden uit
auditpayloads; de auditactie en overige historiek blijven behouden.

## 3. Productiepakket bouwen

Installeer lokaal eerst de Composer-dependencies en bouw daarna het pakket:

```powershell
composer install --no-dev --classmap-authoritative
php bin/build-one-com-package.php
```

Resultaat:

```text
build/aefs-v2-one-com.zip
```

Het pakket bevat de dependencies en legt de publieke assets in de webroot. De
meegeleverde `.htaccess` blokkeert rechtstreekse HTTP-toegang tot onder meer
`config`, `vendor`, `storage`, `app`, `src` en `bin`. Het pakket bevat bewust
geen databasedump, sessies, logs, mailbijlagen of lokale geheimen.

Upload en unzip de **inhoud** van dit pakket in de map van het gekozen
(sub)domein. Controleer dat `.htaccess` werkelijk werd meegekopieerd.

Voer na het invullen van de productieconfiguratie via SSH uit:

```sh
php bin/deployment-readiness.php
```

De controle toont geen wachtwoorden of sleutels en eindigt alleen succesvol als
PHP, extensies, HTTPS-configuratie, schrijfmappen, databasekern en mailbasis
aanwezig zijn.

## 4. Productieconfiguratie

Maak op de server deze bestanden aan op basis van de voorbeelden:

```text
config/local/app.php
config/local/database.php
config/local/mail.php
```

Voor `app.php`:

- `base_url`: volledige HTTPS-URL zonder afsluitende slash;
- `environment`: `production`;
- `app_key`: exact de bestaande, stabiele sleutel.

Voor `database.php` worden uitsluitend de one.com-verbindingsgegevens gebruikt.
Voor `mail.php` blijven SMTP-wachtwoorden buiten Git. Zet de lokale
`mail-recipients.php` niet automatisch over: tijdens de alfa kan op de server
wel eerst een gecontroleerde allowlist worden geplaatst, maar die lijst moet
bewust worden beoordeeld vóór echte bulkmail wordt vrijgegeven.

## 5. Database importeren

Voor een volledig nieuwe, lege installatie is `database/database.sql` de
actuele schema-baseline. Deze maakt geen database aan, verandert geen
databasegebruiker en bevat geen leden- of gebruikersdata.

Voor de echte migratie naar one.com:

1. stop lokale mutaties tijdens het afgesproken cutovervenster;
2. exporteer de actuele lokale database volledig via phpMyAdmin of
   `mysqldump` naar een bestand onder `build/`;
3. versleutel/beveilig die export tijdens opslag en overdracht;
4. selecteer in one.com phpMyAdmin de lege doeldatabase en importeer de
   volledige privé-export;
5. verwijder de overdrachtskopie pas nadat import, applicatietest en aparte
   back-up succesvol zijn afgerond.

De op 17 augustus 2026 voorbereide lokale momentopname staat als genegeerd
bestand in `build/aefs-v2-live-database-20260817.sql`. Maak bij de definitieve
cutover altijd een nieuwe export, zodat tussentijdse wijzigingen mee zijn.

Na import minimaal controleren:

```sql
SELECT COUNT(*) FROM leden;
SELECT COUNT(*) FROM gebruikers;
SELECT COUNT(*) FROM evenementen;
SELECT COUNT(*) FROM shifts;
SELECT COUNT(*) FROM shift_inschrijvingen;
SELECT status, COUNT(*) FROM event_inschrijvingen GROUP BY status;
SELECT status, COUNT(*) FROM shift_inschrijvingen GROUP BY status;
```

Vergelijk de resultaten met de brondatabase van exact hetzelfde cutovermoment.
Voer nooit een oude export over recentere livegegevens uit.

## 6. Schrijfrechten en mailworker

De PHP-gebruiker moet kunnen schrijven in:

```text
storage/cache
storage/logs
storage/mail-attachments
storage/sessions
storage/temp
storage/uploads
```

De browser verstuurt geen mails zelf. Plan op de server iedere minuut:

```sh
php bin/process-mail-queue.php --limit=25
```

Als het one.com-abonnement geen server-side scheduler of SSH biedt, moet vóór
het vrijgeven van automatische mail een veilige externe scheduleroplossing
worden gekozen. Stel geen publiek, onbeveiligd worker-URL in.

## 7. Alfa-smoketest

Voer eerst uit met een beperkte productie-allowlist:

1. login, logout en wachtwoord vergeten;
2. dashboard en PDF-adminhandleiding;
3. lid en groep bekijken/bewerken;
4. concept-event met één dag en één testshift maken;
5. eventinschrijving beoordelen en vrijwilliger administratief toewijzen;
6. aanwezigheid aan- en uitvinken;
7. aanwezigheids- en vergoedingsrapport plus Excel-export;
8. één handmatige testmail inplannen en door de worker laten afleveren;
9. instellingenpagina controleren op productie, tijdzone, mailstatus en app-key;
10. foutlog en mailingdetail controleren zonder foutdetails aan bezoekers te
    tonen.

## 8. Rollback

Houd het vorige webpakket en de pre-import databaseback-up beschikbaar. Bij een
blokkerende fout: zet de site tijdelijk buiten gebruik, herstel eerst de
databaseback-up en daarna het bijbehorende webpakket. Combineer nooit een oude
app-key of oude code met een recentere database zonder gerichte analyse.
