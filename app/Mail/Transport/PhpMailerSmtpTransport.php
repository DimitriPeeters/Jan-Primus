<?php

declare(strict_types=1);

namespace App\Mail\Transport;

use AEFS\Core\Config;
use App\Mail\OutgoingMail;
use App\Mail\RecipientPolicy;
use App\Services\SettingsService;
use PHPMailer\PHPMailer\Exception as PhpMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;
use Throwable;

final class PhpMailerSmtpTransport implements MailTransportInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly RecipientPolicy $recipientPolicy,
        private readonly SettingsService $applicationSettings
    ) {
    }

    public function send(OutgoingMail $mail): ?string
    {
        if (!$this->recipientPolicy->allows($mail->recipientEmail)) {
            throw new RuntimeException(
                'Deze ontvanger is geblokkeerd door de lokale mailtestbeperking.'
            );
        }

        $settings = $this->settings();
        $mailer = new PHPMailer(true);

        try {
            $mailer->isSMTP();
            $mailer->Host = $settings['host'];
            $mailer->Port = $settings['port'];
            $mailer->SMTPAuth = true;
            $mailer->Username = $settings['username'];
            $mailer->Password = $settings['password'];
            $mailer->Timeout = $settings['timeout'];
            $mailer->CharSet = PHPMailer::CHARSET_UTF8;
            $mailer->Encoding = PHPMailer::ENCODING_QUOTED_PRINTABLE;

            $mailer->SMTPSecure = match ($settings['encryption']) {
                'ssl', 'smtps' => PHPMailer::ENCRYPTION_SMTPS,
                'tls', 'starttls' => PHPMailer::ENCRYPTION_STARTTLS,
                default => '',
            };

            $mailer->setFrom(
                $settings['from_address'],
                $settings['from_name']
            );

            if ($settings['reply_to_address'] !== '') {
                $mailer->addReplyTo(
                    $settings['reply_to_address'],
                    $settings['reply_to_name']
                );
            }

            $mailer->addAddress(
                $mail->recipientEmail,
                $mail->recipientName
            );
            $mailer->isHTML(true);
            $mailer->Subject = $mail->subject;
            $mailer->Body = $mail->html;
            $mailer->AltBody = $mail->text;

            foreach ($mail->attachments as $attachment) {
                $mailer->addAttachment(
                    $attachment['path'],
                    $attachment['name']
                );
            }

            $mailer->send();

            $messageId = trim(
                (string) $mailer->getLastMessageID(),
                "<> \t\n\r\0\x0B"
            );

            return $messageId !== '' ? $messageId : null;
        } catch (PhpMailerException $exception) {
            throw new RuntimeException(
                'De SMTP-server heeft de mail niet aanvaard: '
                . $this->safeError($exception->getMessage()),
                0,
                $exception
            );
        } catch (Throwable $throwable) {
            throw new RuntimeException(
                'De mail kon niet via SMTP worden verstuurd: '
                . $this->safeError($throwable->getMessage()),
                0,
                $throwable
            );
        }
    }

    /**
     * @return array{
     *     host: string,
     *     port: int,
     *     encryption: string,
     *     username: string,
     *     password: string,
     *     from_address: string,
     *     from_name: string,
     *     reply_to_address: string,
     *     reply_to_name: string,
     *     timeout: int
     * }
     */
    private function settings(): array
    {
        if (!(bool) $this->config->get('mail.enabled', false)) {
            throw new RuntimeException(
                'Mailverzending is nog niet ingeschakeld.'
            );
        }

        $settings = [
            'host' => trim((string) $this->config->get('mail.host', '')),
            'port' => (int) $this->config->get('mail.port', 587),
            'encryption' => strtolower(
                trim((string) $this->config->get('mail.encryption', 'tls'))
            ),
            'username' => trim((string) $this->config->get('mail.username', '')),
            'password' => (string) $this->config->get('mail.password', ''),
            'from_address' => trim(
                (string) $this->config->get('mail.from_address', '')
            ),
            'from_name' => trim(
                $this->applicationSettings->mailFromName()
            ),
            'reply_to_address' => trim(
                $this->applicationSettings->mailReplyToAddress()
                    ?: (string) $this->config->get(
                        'mail.reply_to_address',
                        ''
                    )
            ),
            'reply_to_name' => trim(
                $this->applicationSettings->mailReplyToName()
            ),
            'timeout' => max(
                5,
                (int) $this->config->get('mail.timeout_seconds', 20)
            ),
        ];

        if (
            $settings['host'] === ''
            || $settings['port'] <= 0
            || $settings['username'] === ''
            || $settings['password'] === ''
            || !filter_var(
                $settings['from_address'],
                FILTER_VALIDATE_EMAIL
            )
        ) {
            throw new RuntimeException(
                'De lokale SMTP-configuratie is onvolledig.'
            );
        }

        return $settings;
    }

    private function safeError(string $message): string
    {
        $password = (string) $this->config->get('mail.password', '');

        if ($password !== '') {
            $message = str_replace($password, '[afgeschermd]', $message);
        }

        $message = trim($message);

        return function_exists('mb_substr')
            ? mb_substr($message, 0, 1000)
            : substr($message, 0, 1000);
    }
}
