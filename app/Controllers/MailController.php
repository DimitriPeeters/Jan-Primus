<?php

declare(strict_types=1);

namespace App\Controllers;

use AEFS\Core\Auth;
use AEFS\Core\Config;
use AEFS\Core\Http\JsonResponse;
use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;
use AEFS\Core\Http\UploadedFile;
use AEFS\Core\Session;
use AEFS\Core\View\Helper\CsrfHelper;
use AEFS\Core\View\ViewFactory;
use App\Http\Requests\MailingRequest;
use App\Services\MailQueueProcessor;
use App\Services\MailService;
use DomainException;
use Throwable;

final class MailController extends BaseController
{
    public function __construct(
        ViewFactory $views,
        Request $request,
        private readonly MailService $service,
        private readonly CsrfHelper $csrf,
        private readonly Config $config,
        private readonly MailQueueProcessor $queueProcessor
    ) {
        parent::__construct($views, $request);
    }

    public function index(): Response
    {
        return $this->view(
            'mailings.index',
            [
                'title' => 'Mailings',
                'mailings' => $this->service->latest(),
                'totals' => $this->service->totals(),
                'mailConfigured' => $this->mailConfigured(),
                'smtpHost' => (string) $this->config->get(
                    'mail.host',
                    ''
                ),
                'recipientRestriction' => $this->service
                    ->recipientRestriction(),
            ]
        );
    }

    public function processScheduledQueue(): Response
    {
        try {
            $result = $this->queueProcessor->process();

            return new JsonResponse(
                [
                    'success' => true,
                    'message' => 'De mailwachtrij werd verwerkt.',
                    'processed' => $result['processed'],
                    'sent' => $result['sent'],
                    'failed' => $result['failed'],
                ],
                200,
                $this->schedulerResponseHeaders()
            );
        } catch (Throwable $throwable) {
            error_log(sprintf(
                'AEFS mailworker schedulerfout (%s).',
                $throwable::class
            ));

            return new JsonResponse(
                [
                    'success' => false,
                    'message' => 'De mailwachtrij kon niet worden verwerkt.',
                ],
                503,
                $this->schedulerResponseHeaders()
            );
        }
    }

    public function create(): Response
    {
        return $this->view(
            'mailings.create',
            [
                'title' => 'Nieuwe mailing',
                'options' => $this->service->audienceOptions(),
                'mailConfigured' => $this->mailConfigured(),
                'recipientRestriction' => $this->service
                    ->recipientRestriction(),
            ]
        );
    }

    public function store(): Response
    {
        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);
            $request = new MailingRequest($input);
            $attachment = $this->request()->file('bijlage');

            if (is_array($attachment)) {
                throw new DomainException(
                    'Er kan per mailing maximaal één bijlage worden toegevoegd.'
                );
            }

            $mailingId = $this->service->queueManual(
                $request->all(),
                $attachment instanceof UploadedFile ? $attachment : null,
                $this->requireUserId()
            );

            $this->success(
                'De mailing werd gepersonaliseerd en in de verzendwachtrij geplaatst.'
            );

            return $this->redirect('/mailings/' . $mailingId);
        } catch (Throwable $throwable) {
            unset($input['_token']);
            Session::flash('_old_input', $input);
            Session::flash(
                '_errors',
                [
                    'form' => [
                        $throwable->getMessage(),
                    ],
                ]
            );
            $this->error(
                'De mailing kon niet worden ingepland.'
            );

            return $this->redirect('/mailings/create');
        }
    }

    public function show(): Response
    {
        $mailingId = $this->routeId();
        $mailing = $this->service->find($mailingId);

        if ($mailing === null) {
            return $this->view(
                'core::errors.404',
                [
                    'title' => 'Mailing niet gevonden',
                    'message' => 'De gevraagde mailing bestaat niet.',
                ],
                404
            );
        }

        return $this->view(
            'mailings.show',
            [
                'title' => $mailing->subject,
                'mailing' => $mailing,
                'recipients' => $this->service->recipients($mailingId),
                'recipientRestriction' => $this->service
                    ->recipientRestriction(),
            ]
        );
    }

    public function retry(): Response
    {
        $mailingId = $this->routeId();
        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);
            $count = $this->service->retryFailed(
                $mailingId,
                $this->requireUserId()
            );

            $this->success(
                $count > 0
                    ? sprintf(
                        '%d mislukte ontvanger(s) werden opnieuw ingepland.',
                        $count
                    )
                    : 'Er waren geen mislukte ontvangers om opnieuw in te plannen.'
            );
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());
        }

        return $this->redirect('/mailings/' . $mailingId);
    }

    public function sendShiftPlanning(): Response
    {
        $eventId = $this->routeId();
        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);
            $mailingId = $this->service->queueShiftPlanning(
                $eventId,
                $this->requireUserId()
            );

            $this->success(
                'De persoonlijke shiftoverzichten werden in de verzendwachtrij geplaatst.'
            );

            return $this->redirect('/mailings/' . $mailingId);
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());

            return $this->redirect('/events/' . $eventId);
        }
    }

    /**
     * @param array<string, mixed> $input
     */
    private function validateCsrf(array $input): void
    {
        $token = $input['_token'] ?? null;

        if (!is_string($token) || !$this->csrf->validate($token)) {
            throw new DomainException(
                'Ongeldige of verlopen beveiligingstoken.'
            );
        }
    }

    private function requireUserId(): int
    {
        $userId = Auth::id();

        if ($userId === null || $userId <= 0) {
            throw new DomainException(
                'Je moet aangemeld zijn om deze actie uit te voeren.'
            );
        }

        return $userId;
    }

    private function routeId(): int
    {
        return (int) $this->request()->route('id', 0);
    }

    private function mailConfigured(): bool
    {
        return (bool) $this->config->get('mail.enabled', false)
            && trim((string) $this->config->get('mail.host', '')) !== ''
            && trim((string) $this->config->get('mail.username', '')) !== ''
            && trim((string) $this->config->get('mail.password', '')) !== ''
            && filter_var(
                $this->config->get('mail.from_address', ''),
                FILTER_VALIDATE_EMAIL
            ) !== false;
    }

    /**
     * @return array<string, string>
     */
    private function schedulerResponseHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }
}
