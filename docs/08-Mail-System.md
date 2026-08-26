# AEFS v2 mailsysteem

## Architectuur

AEFS verstuurt nooit SMTP-verkeer tijdens een normale HTTP-request.
Event- en beheeracties schrijven gepersonaliseerde afleveringen naar:

```text
mailings
mailing_ontvangers
mailing_bijlagen
```

De CLI-worker verwerkt de wachtrij in begrensde batches:

```powershell
php bin/process-mail-queue.php --limit=25
```

Plan dit commando in productie elke minuut in via cron of de scheduler van de
hostingomgeving. Gelijktijdige workers zijn toegestaan; ontvangers worden met
database-rowlocking geclaimd.

## Externe scheduler op SFTP-only hosting

Wanneer de productiehosting geen SSH of server-cron aanbiedt, kan een externe
HTTPS-scheduler dezelfde queueprocessor activeren via:

```text
POST /internal/mail-worker/process
X-Jan-Primus-Worker-Token: <256-bit token>
```

Deze route is standaard uitgeschakeld. Ze wordt uitsluitend actief via het
genegeerde `config/local/mail_worker.php`. Maak dat bestand lokaal aan met:

```powershell
php bin/generate-mail-worker-config.php
```

De gegenereerde token bestaat uit 64 willekeurige hextekens. De token mag
uitsluitend in de HTTPS-header staan: nooit in de URL, querystring, requestbody,
repository of schermafbeelding. Een request zonder geldige token, via HTTP of
met een ongeldige configuratie bereikt de `MailQueueProcessor` niet.

Voor cron-job.org of een gelijkwaardige dienst:

```text
Frequentie: iedere minuut
Methode: POST
URL: https://alleventsforeversure.be/internal/mail-worker/process
Headernaam: X-Jan-Primus-Worker-Token
Headerwaarde: token uit config/local/mail_worker.php
Body: leeg
```

Schakel foutmeldingen bij opeenvolgende mislukte runs in. De JSON-response
bevat alleen veilige aantallen en nooit mailinhoud, ontvangers of geheimen. De
browser hoeft ook bij deze fallback niet open te blijven.

De browser hoeft na het inplannen van een mailing niet open te blijven. De
webrequest bewaart alle ontvangers eerst in de database; de worker handelt de
feitelijke verzending daarna af. De server, PHP en database moeten wel actief
en bereikbaar blijven.

## Lokale Windows-taak

Voor de lokale Laragon-omgeving kan de worker als Windows-taak worden
geregistreerd. Voer PowerShell uit onder de Windows-gebruiker die de lokale
omgeving gebruikt:

```powershell
powershell -ExecutionPolicy Bypass -File bin/install-mail-worker-task.ps1 `
    -PhpPath "C:\laragon\bin\php\php-8.4.23-nts-Win32-vs17-x64\php.exe"
```

De taak draait iedere minuut, verwerkt maximaal 25 ontvangers per run en start
geen overlappende instantie. Hij blijft werken wanneer de browser gesloten is,
maar alleen zolang deze Windows-gebruiker aangemeld is en Laragon/MySQL
bereikbaar zijn. Relevante runs en fouten verschijnen in:

```text
storage/logs/mail-worker.log
```

Een lege wachtrij wordt niet iedere minuut gelogd. De lokale Windows-taak
vervangt niet de productieplanning: bij deployment moet hetzelfde CLI-commando
nog via de scheduler of cronfunctie van de hosting worden ingesteld.

## Lokale configuratie

Kopieer:

```text
config/local/mail.example.php
```

naar:

```text
config/local/mail.php
```

Het doelbestand wordt door Git genegeerd. Commit SMTP-gebruikersnamen,
wachtwoorden of app-wachtwoorden nooit.

## Veilige lokale testmodus

Kopieer voor lokale end-to-endtests:

```text
config/local/mail-recipients.example.php
```

naar het genegeerde:

```text
config/local/mail-recipients.php
```

Zolang dit bestand een of meer adressen bevat, neemt AEFS alleen die adressen
in mailings op. De SMTP-transportlaag controleert dezelfde lijst nogmaals vlak
voor verzending. Zo kan een publicatieflow lokaal worden getest zonder andere
leden te bereiken. Een lege lijst schakelt de beperking uit; doe dat pas nadat
de productieconfiguratie, quota en monitoring gecontroleerd zijn.

## Gmail

De standaardconfiguratie gebruikt:

```text
host: smtp.gmail.com
port: 587
encryption: tls
```

Schakel tweestapsverificatie in op het Google-account en maak daarna een
app-wachtwoord voor AEFS aan. Gebruik niet het gewone accountwachtwoord.

Een persoonlijk Gmail-account heeft relatief lage verzendquota. De AEFS-worker
verstuurt daarom begrensde batches en één gepersonaliseerde mail per ontvanger.
Voor structureel grotere aantallen is Google Workspace SMTP relay of een
transactionele mailprovider geschikter.

## one.com

Voor een one.com-mailbox vanaf een externe host vervang je alleen de lokale
SMTP-instellingen:

```text
host: send.one.com
port: 587
encryption: tls
username: volledig one.com-e-mailadres
password: mailboxwachtwoord
```

Wanneer AEFS zelf op one.com wordt gehost, gebruik je volgens one.com
`mailout.one.com` in plaats van `send.one.com`, eveneens met poort 587 en TLS.

De queue, templates en domeinflows wijzigen niet wanneer de provider wisselt.

## Automatische mails

Momenteel worden mails ingepland voor:

- de overgang van een evenement naar `gepubliceerd`;
- een evenementinschrijving die `bevestigd` wordt;
- een evenementinschrijving die `reserve` wordt;
- een evenement dat naar `geannuleerd` gaat, gericht aan actieve
  eventinschrijvingen en bevestigde shiftvrijwilligers;
- een beheerder die de persoonlijke shiftplanning van een evenement verstuurt.

Een gewone wijziging aan een reeds gepubliceerd evenement veroorzaakt geen
nieuwe publicatiemail.

Bij een volledige evenementannulatie wordt het evenement onmiddellijk als
geannuleerd zichtbaar. De actieve eventinschrijvingen, shifts en bijbehorende
shifttoewijzingen blijven historisch intact totdat alle kennisgevingen
succesvol zijn afgeleverd. Daarna rondt de achtergrondworker de annulatie
transactioneel af. Een gedeeltelijk mislukte mailing blokkeert die afronding;
de beheerder kan de mislukte ontvangers vanuit het mailingdetail opnieuw
inplannen.

Annulatieaanvragen van individuele leden blijven als platformmelding voor
beheerders beschikbaar. Hiervoor wordt bewust geen beheerdersmail verstuurd.

## Manuele mailings

Onder **Mailings → Nieuwe mailing** kan een administrator één bericht en
optioneel één bijlage toevoegen. Doelgroepen zijn:

- alle actieve leden;
- één of meer ledengroepen;
- actieve inschrijvingen van één of meer evenementen;
- bevestigde of reserveleden van één of meer shifts.

AEFS dedupliceert ontvangers en respecteert `gebruikers.mail_blacklist`.
Iedere ontvanger krijgt een afzonderlijk `To`-bericht.

## Fouten en retries

De worker bewaart per ontvanger het aantal pogingen, de volgende poging en de
laatste fout. Tijdelijke fouten worden automatisch uitgesteld. Na het maximale
aantal pogingen kan een administrator de mislukte afleveringen via de
mailingdetailpagina opnieuw inplannen.

`evenementen.planning_verstuurd` wordt uitsluitend ingevuld nadat alle
persoonlijke shiftoverzichten werkelijk zijn afgeleverd.
