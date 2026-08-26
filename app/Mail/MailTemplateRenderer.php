<?php

declare(strict_types=1);

namespace App\Mail;

use AEFS\Core\Config;
use App\Models\Event;
use App\Models\Shift;
use App\Services\SettingsService;
use DateTimeImmutable;
use RuntimeException;

final class MailTemplateRenderer
{
    public function __construct(
        private readonly Config $config,
        private readonly SettingsService $settings
    ) {
    }

    public function eventPublished(
        Event $event,
        string $firstName
    ): MailContent {
        $subject = 'Nieuw evenement: ' . $event->titel;
        $location = $event->hasLocation()
            ? 'Locatie: ' . $event->locatie
            : null;
        $description = $event->hasDescription()
            ? (string) $event->beschrijving
            : 'Bekijk het evenement in AEFS voor alle praktische informatie.';
        $details = array_filter([
            'Periode: ' . $event->displayDate(),
            $location,
        ]);

        $intro = 'Er werd een nieuw evenement gepubliceerd waarvoor je je kunt inschrijven.';
        $instructions = 'Je kunt je registreren voor één of meerdere eventdagen. Daarna kun je één of meerdere beschikbare shifts kiezen. Shifts mogen elkaar niet overlappen en je keuzes worden door een administrator beoordeeld.';

        $html = $this->paragraph($intro);
        $html .= $this->paragraph($instructions);
        $html .= $this->eventCard($event, $description);
        $html .= $this->button(
            'Eventdagen kiezen',
            '/events/' . $event->eventId
        );
        $html .= $this->button(
            'Beschikbare shifts bekijken',
            '/shifts'
        );

        $text = implode(PHP_EOL, [
            $this->greeting($firstName),
            '',
            $intro,
            $instructions,
            '',
            $event->titel,
            ...$details,
            $description,
            $this->plainUrl('/events/' . $event->eventId),
            $this->plainUrl('/shifts'),
        ]);

        return new MailContent(
            $subject,
            $this->layout($subject, $firstName, $html),
            $this->plainLayout($text)
        );
    }

    public function eventDecision(
        Event $event,
        string $firstName,
        string $status
    ): MailContent {
        $confirmed = $status === 'bevestigd';
        $subject = $confirmed
            ? 'Je deelname aan ' . $event->titel . ' is bevestigd'
            : 'Je staat op reserve voor ' . $event->titel;
        $message = $confirmed
            ? 'Je inschrijving voor dit evenement werd bevestigd.'
            : 'Je inschrijving werd op de reservelijst geplaatst. We houden je op de hoogte wanneer je deelname alsnog kan worden bevestigd.';

        $html = $this->paragraph($message);
        $html .= $this->eventCard(
            $event,
            $event->beschrijving ?? ''
        );
        $html .= $this->button(
            'Mijn inschrijving bekijken',
            '/events/' . $event->eventId
        );

        $text = implode(PHP_EOL, [
            $this->greeting($firstName),
            '',
            $message,
            '',
            $event->titel,
            'Periode: ' . $event->displayDate(),
            $event->hasLocation() ? 'Locatie: ' . $event->locatie : '',
            $this->plainUrl('/events/' . $event->eventId),
        ]);

        return new MailContent(
            $subject,
            $this->layout($subject, $firstName, $html),
            $this->plainLayout($text)
        );
    }

    public function eventCancelled(
        Event $event,
        string $firstName,
        bool $hasConfirmedShift
    ): MailContent {
        $subject = 'Evenement geannuleerd: ' . $event->titel;
        $message = 'Het evenement gaat niet door. Je actieve inschrijving voor dit evenement wordt geannuleerd.';
        $shiftMessage = $hasConfirmedShift
            ? 'Ook je bevestigde shifttoewijzing(en) voor dit evenement worden geannuleerd.'
            : null;
        $html = $this->paragraph($message);

        if ($shiftMessage !== null) {
            $html .= $this->paragraph($shiftMessage);
        }

        $html .= $this->eventCard(
            $event,
            'Dit evenement werd door de organisatie geannuleerd.'
        );

        $text = implode(
            PHP_EOL,
            array_filter(
                [
                    $this->greeting($firstName),
                    '',
                    $message,
                    $shiftMessage,
                    '',
                    $event->titel,
                    'Periode: ' . $event->displayDate(),
                    $event->hasLocation()
                        ? 'Locatie: ' . $event->locatie
                        : null,
                ],
                static fn(?string $line): bool => $line !== null
            )
        );

        return new MailContent(
            $subject,
            $this->layout($subject, $firstName, $html),
            $this->plainLayout($text)
        );
    }

    /**
     * @param array<int, array<string, mixed>> $shifts
     */
    public function shiftPlanning(
        Event $event,
        string $firstName,
        array $shifts
    ): MailContent {
        $subject = 'Jouw shiftplanning voor ' . $event->titel;
        $html = $this->paragraph(
            'Hieronder vind je jouw bevestigde shifts. Bewaar dit overzicht bij je planning.'
        );
        $html .= '<div style="margin:20px 0">';
        $textLines = [
            $this->greeting($firstName),
            '',
            'Hieronder vind je jouw bevestigde shifts voor ' . $event->titel . '.',
            '',
        ];

        foreach ($shifts as $shift) {
            $start = new DateTimeImmutable((string) $shift['start_op']);
            $end = new DateTimeImmutable((string) $shift['eind_op']);
            $name = trim((string) ($shift['shift_naam'] ?? ''));

            if ($name === '') {
                $name = (string) ($shift['type_naam'] ?? 'Shift');
            }

            $period = $start->format('d/m/Y H:i')
                . ' – '
                . $end->format(
                    $start->format('Y-m-d') === $end->format('Y-m-d')
                        ? 'H:i'
                        : 'd/m/Y H:i'
                );

            $html .= sprintf(
                '<div style="padding:14px 16px;margin:0 0 10px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc">'
                . '<strong style="display:block;color:#172033">%s</strong>'
                . '<span style="display:block;margin-top:5px;color:#475569">%s</span>'
                . '</div>',
                $this->escape($name),
                $this->escape($period)
            );

            $textLines[] = '- ' . $name . ': ' . $period;
        }

        $html .= '</div>';
        $html .= $this->button(
            'Evenement bekijken',
            '/events/' . $event->eventId
        );
        $textLines[] = '';
        $textLines[] = $this->plainUrl(
            '/events/' . $event->eventId
        );

        return new MailContent(
            $subject,
            $this->layout($subject, $firstName, $html),
            $this->plainLayout(implode(PHP_EOL, $textLines))
        );
    }

    public function shiftUpdated(
        Shift $oldShift,
        Shift $updatedShift,
        string $firstName
    ): MailContent {
        $subject = 'Shift gewijzigd: ' . $updatedShift->displayNaam();
        $intro = 'De gegevens van een shift waarvoor je bent ingeschreven zijn gewijzigd.';
        $changes = [];

        $comparisons = [
            'Functie' => [$oldShift->displayType(), $updatedShift->displayType()],
            'Naam' => [$oldShift->displayNaam(), $updatedShift->displayNaam()],
            'Datum en uren' => [$oldShift->displayPeriode(), $updatedShift->displayPeriode()],
            'Capaciteit' => [(string) $oldShift->maxPersonen, (string) $updatedShift->maxPersonen],
        ];

        foreach ($comparisons as $label => [$oldValue, $newValue]) {
            if ($oldValue !== $newValue) {
                $changes[] = sprintf(
                    '%s: %s → %s',
                    $label,
                    $oldValue,
                    $newValue
                );
            }
        }

        $html = $this->paragraph($intro);
        $html .= sprintf(
            '<div style="padding:16px;margin:20px 0;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc">'
            . '<strong style="display:block;color:#172033">%s</strong>'
            . '<span style="display:block;margin-top:6px;color:#475569">%s</span>'
            . '<span style="display:block;margin-top:4px;color:#475569">Functie: %s</span>'
            . '<span style="display:block;margin-top:4px;color:#475569">Capaciteit: %d personen</span>'
            . '</div>',
            $this->escape($updatedShift->displayNaam()),
            $this->escape($updatedShift->displayPeriode()),
            $this->escape($updatedShift->displayType()),
            $updatedShift->maxPersonen
        );

        if ($changes !== []) {
            $html .= '<p style="margin:20px 0 8px;color:#172033"><strong>Wat is gewijzigd?</strong></p><ul style="margin:0 0 20px;padding-left:22px;color:#475569">';

            foreach ($changes as $change) {
                $html .= '<li style="margin:5px 0">' . $this->escape($change) . '</li>';
            }

            $html .= '</ul>';
        }

        $html .= $this->button(
            'Shift bekijken',
            '/shifts/' . $updatedShift->shiftId
        );

        $text = implode(PHP_EOL, [
            $this->greeting($firstName),
            '',
            $intro,
            '',
            $updatedShift->displayNaam(),
            'Datum en uren: ' . $updatedShift->displayPeriode(),
            'Functie: ' . $updatedShift->displayType(),
            'Capaciteit: ' . $updatedShift->maxPersonen . ' personen',
            '',
            'Wat is gewijzigd?',
            ...array_map(
                static fn(string $change): string => '- ' . $change,
                $changes
            ),
            $this->plainUrl('/shifts/' . $updatedShift->shiftId),
        ]);

        return new MailContent(
            $subject,
            $this->layout($subject, $firstName, $html),
            $this->plainLayout($text)
        );
    }

    public function passwordReset(
        string $firstName,
        string $token
    ): MailContent {
        $subject = 'Stel je wachtwoord voor AEFS opnieuw in';
        $path = '/reset-password/' . rawurlencode($token);
        $url = $this->absoluteUrl($path);

        if ($url === null) {
            throw new RuntimeException(
                'De applicatie-URL voor automatische mails is niet ingesteld.'
            );
        }

        $html = $this->paragraph(
            'We ontvingen een aanvraag om het wachtwoord van je AEFS-account opnieuw in te stellen.'
        );
        $html .= $this->paragraph(
            'Deze persoonlijke herstelkoppeling blijft één uur geldig en kan maar één keer worden gebruikt. Heb je dit niet aangevraagd, dan hoef je niets te doen.'
        );
        $html .= $this->button(
            'Nieuw wachtwoord instellen',
            $path
        );

        $text = implode(PHP_EOL, [
            $this->greeting($firstName),
            '',
            'We ontvingen een aanvraag om het wachtwoord van je AEFS-account opnieuw in te stellen.',
            'Deze persoonlijke herstelkoppeling blijft één uur geldig en kan maar één keer worden gebruikt.',
            '',
            $url,
            '',
            'Heb je dit niet aangevraagd, dan hoef je niets te doen.',
        ]);

        return new MailContent(
            $subject,
            $this->layout($subject, $firstName, $html),
            $this->plainLayout($text)
        );
    }

    public function passwordResetSummary(): MailContent
    {
        $subject = 'Stel je wachtwoord voor AEFS opnieuw in';
        $message = 'Een persoonlijke wachtwoordherstelmail werd aangemaakt. De beveiligde herstelkoppeling wordt niet in dit beheeroverzicht getoond.';

        return new MailContent(
            $subject,
            $this->layout(
                $subject,
                '',
                $this->paragraph($message)
            ),
            $this->plainLayout($message)
        );
    }

    public function manual(
        string $subject,
        string $body,
        string $firstName
    ): MailContent {
        $html = $this->paragraphs($body);
        $text = implode(PHP_EOL, [
            $this->greeting($firstName),
            '',
            trim($body),
        ]);

        return new MailContent(
            $subject,
            $this->layout($subject, $firstName, $html),
            $this->plainLayout($text)
        );
    }

    private function eventCard(Event $event, string $description): string
    {
        $location = $event->hasLocation()
            ? '<br><strong>Locatie:</strong> '
                . $this->escape((string) $event->locatie)
            : '';

        return sprintf(
            '<div style="margin:20px 0;padding:18px;border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc">'
            . '<h2 style="margin:0 0 10px;font-size:20px;color:#172033">%s</h2>'
            . '<p style="margin:0 0 12px;color:#475569"><strong>Periode:</strong> %s%s</p>'
            . '<p style="margin:0;color:#334155;white-space:pre-line">%s</p>'
            . '</div>',
            $this->escape($event->titel),
            $this->escape($event->displayDate()),
            $location,
            nl2br($this->escape(trim($description)))
        );
    }

    private function layout(
        string $preheader,
        string $firstName,
        string $content
    ): string {
        return '<!doctype html><html lang="nl-BE"><head><meta charset="UTF-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . $this->escape($preheader) . '</title></head>'
            . '<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,sans-serif;color:#172033">'
            . '<span style="display:none;max-height:0;overflow:hidden">'
            . $this->escape($preheader)
            . '</span>'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9">'
            . '<tr><td align="center" style="padding:24px 12px">'
            . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#fff;border-radius:12px;overflow:hidden">'
            . '<tr><td style="padding:20px 28px;background:#b5121b;color:#fff;font-weight:700;font-size:20px">'
            . $this->escape($this->settings->applicationName())
            . '</td></tr>'
            . '<tr><td style="padding:28px;line-height:1.6">'
            . '<p style="margin:0 0 18px">'
            . $this->escape($this->greeting($firstName))
            . '</p>'
            . $content
            . '<p style="margin:28px 0 0">Met vriendelijke groet,<br><strong>'
            . $this->escape($this->settings->organizationName())
            . '</strong></p>'
            . '</td></tr></table>'
            . '<p style="margin:14px 0 0;color:#64748b;font-size:12px">Deze mail werd verstuurd vanuit '
            . $this->escape($this->settings->applicationName())
            . '.</p>'
            . '</td></tr></table></body></html>';
    }

    private function paragraphs(string $body): string
    {
        $paragraphs = preg_split(
            '/\R{2,}/',
            trim($body)
        ) ?: [];

        return implode(
            '',
            array_map(
                fn(string $paragraph): string => $this->paragraph($paragraph),
                $paragraphs
            )
        );
    }

    private function paragraph(string $text): string
    {
        return '<p style="margin:0 0 16px">'
            . nl2br($this->escape(trim($text)))
            . '</p>';
    }

    private function button(string $label, string $path): string
    {
        $url = $this->absoluteUrl($path);

        if ($url === null) {
            return '';
        }

        return sprintf(
            '<p style="margin:22px 0 0"><a href="%s" style="display:inline-block;padding:12px 18px;border-radius:8px;background:#b5121b;color:#fff;text-decoration:none;font-weight:700">%s</a></p>',
            $this->escape($url),
            $this->escape($label)
        );
    }

    private function plainUrl(string $path): string
    {
        $url = $this->absoluteUrl($path);

        return $url !== null ? 'Meer informatie: ' . $url : '';
    }

    private function absoluteUrl(string $path): ?string
    {
        $base = rtrim(
            trim((string) $this->config->get('mail.application_url', '')),
            '/'
        );

        if ($base === '') {
            return null;
        }

        return $base . '/' . ltrim($path, '/');
    }

    private function greeting(string $firstName): string
    {
        $firstName = trim($firstName);

        return $firstName !== ''
            ? 'Beste ' . $firstName . ','
            : 'Beste,';
    }

    private function plainLayout(string $content): string
    {
        return trim($content)
            . PHP_EOL
            . PHP_EOL
            . 'Met vriendelijke groet,'
            . PHP_EOL
            . $this->settings->organizationName();
    }

    private function escape(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}
