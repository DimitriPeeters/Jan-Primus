# Jan Primus Ledenbeheer deployment naar one.com

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
build/jan-primus-ledenbeheer-one-com.zip
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

## 5. Bestaande one.com-data veilig samenvoegen

Voor een volledig nieuwe, lege installatie is `database/database.sql` de
actuele schema-baseline. Deze maakt geen database aan, verandert geen
databasegebruiker en bevat geen leden- of gebruikersdata.

De bestaande one.com-database bevat nog gegevens uit de oude frontend en mag
daarom niet eenvoudig worden vervangen door alleen de lokale database. Bouw de
private cutoverexport lokaal met:

```powershell
php database/migrations/20260818_000008_build_onecom_cutover.php `
  --onecom-export=build/onecom-before-aefs-20260818.sql `
  --legacy-project=D:/AEFS_ledenadministratie.zip `
  --replace-target
```

De builder:

- gebruikt de actuele lokale `aefs_v2` als definitieve schema- en
  applicatiebasis;
- voegt one.com-leden, gebruikers, inschrijvingen, aanwezigheid,
  contactberichten en meldingen data-behoudend samen;
- behoudt conflicterende bron-ID's waar mogelijk en maakt anders uitsluitend
  interne, gecontroleerde mappings;
- archiveert de oude shifttabellen onder
  `*_onecom_legacy_20260818`, maar activeert `event_shifts`,
  `shift_toewijzingen` en `shift_registrations` niet opnieuw;
- verwijdert bij de uiteindelijke import pas daarna de obsolete actieve
  legacytabellen en het niet te behouden `mail_logs`;
- versleutelt alle nationale identificatienummers en rekeningnummers met de
  stabiele AEFS-`app_key`;
- importeert eerst de oude one.com-export in een aparte testdatabase, vervangt
  die vervolgens volledig door het eindresultaat en controleert aantallen,
  statussen, duplicaten, verweesde relaties, shifttijden, encryptie en foreign
  keys. Daardoor worden ook conflicten met oude, incompatibele tabellen vóór de
  live-import ontdekt.

De one.com-export, het oude project met de legacy-encryptiesleutel en alle
gegenereerde bestanden blijven lokaal en privé. Ze mogen niet worden gecommit,
gepusht of in het webpakket terechtkomen. Een geslaagde run levert op:

```text
build/aefs-v2-one-com-cutover.sql
build/aefs-v2-one-com-cutover-report.json
```

Controleer dat de SHA-256 van de dump overeenkomt met het rapport. De builder
mag alleen tijdelijke databases verwijderen waarvan de naam met
`aefs_v2_cutover_` begint; de lokale brondatabase wordt uitsluitend gelezen.

Voor de echte migratie naar de enige one.com-database:

1. stop lokale mutaties tijdens het afgesproken cutovervenster;
2. zet ook de oude frontend in onderhoud zodat daar geen registratie meer kan
   wijzigen;
3. maak een nieuwe one.com-back-up en vervang daarmee het lokale
   `onecom-before-aefs-20260818.sql`;
4. voer de cutoverbuilder opnieuw uit en controleer het JSON-rapport;
5. bewaar de pre-cutover one.com-back-up op een tweede, beveiligde locatie;
6. selecteer in one.com phpMyAdmin de bestaande database en importeer
   `aefs-v2-one-com-cutover.sql`; de dump schakelt foreign-keycontroles
   tijdelijk uit, verwijdert eerst alle te vervangen tabellen en bouwt daarna
   het volledig samengevoegde en geteste schema op;
7. verwijder de overdrachtskopie pas nadat import, applicatietest en aparte
   back-up succesvol zijn afgerond.

Gebruik nooit `database/database.sql` of alleen
`aefs-v2-live-database-20260817.sql` voor deze cutover: beide missen de
samengevoegde one.com-gegevens. Voer nooit een oudere private cutoverdump over
recentere livegegevens uit.

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

Vergelijk de resultaten met `table_counts` en de statusverdelingen in het
JSON-rapport van exact hetzelfde cutovermoment.

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

### one.com zonder SSH of cron

Gebruik op het SFTP-only pakket de beveiligde externe scheduleringang. Maak de
genegeerde productieconfiguratie lokaal aan, bijvoorbeeld rechtstreeks in de
uitgepakte uploadmap:

```powershell
php bin/generate-mail-worker-config.php `
    --output="build/aefs-v2-one-com-upload/config/local/mail_worker.php"
```

Upload `config/local/mail_worker.php` daarna via SFTP. Het bestand bevat een
geheime 256-bit token en mag niet worden gecommit, gemaild of gedeeld.

Configureer de externe scheduler iedere minuut met:

```text
Methode: POST
URL: https://alleventsforeversure.be/internal/mail-worker/process
Header: X-Jan-Primus-Worker-Token: <token uit config/local/mail_worker.php>
Body: leeg
```

Gebruik uitsluitend HTTPS, plaats de token nooit in de URL en bewaar geen
requestheaders in publiek toegankelijke logs. De endpoint verwerkt dezelfde
`MailQueueProcessor` als de CLI-worker, met dezelfde batchlimiet, retries en
database-locking. Een ontbrekende of foutieve token wordt afgewezen voordat de
wachtrij wordt aangeraakt.

De instellingenpagina toont alleen of de beveiligde scheduleringang correct is
geconfigureerd; controleer daarnaast de uitvoeringshistoriek en
foutmeldingen van de externe scheduler.

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
