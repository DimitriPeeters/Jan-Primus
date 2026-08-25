<?php

declare(strict_types=1);

namespace App\Controllers;

use AEFS\Core\Auth;
use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;
use AEFS\Core\Session;
use AEFS\Core\View\Helper\CsrfHelper;
use AEFS\Core\View\ViewFactory;
use App\Http\Requests\EventCancellationRequest;
use App\Http\Requests\EventRegistrationRequest;
use App\Http\Requests\EventRequest;
use App\Services\EventService;
use RuntimeException;
use Throwable;

final class EventController extends BaseController
{
    public function __construct(
        ViewFactory $views,
        Request $request,
        private readonly EventService $service,
        private readonly CsrfHelper $csrf
    ) {
        parent::__construct(
            $views,
            $request
        );
    }

    public function index(): Response
    {
        $zoekterm = trim(
            (string) $this->request()->query->get(
                'zoek',
                ''
            )
        );

        $isAdmin = Auth::isAdmin();

        if ($isAdmin) {
            $events = $zoekterm === ''
                ? $this->service->allForAdministration()
                : $this->service->searchForAdministration($zoekterm);
        } else {
            $events = $zoekterm === ''
                ? $this->service->visibleToMembers()
                : $this->service->searchVisibleToMembers($zoekterm);
        }

        return $this->view(
            'events.index',
            [
                'title' => 'Evenementen',
                'events' => $events,
                'zoekterm' => $zoekterm,
                'isAdmin' => $isAdmin,
            ]
        );
    }

    public function show(): Response
    {
        $id = $this->routeId();
        $isAdmin = Auth::isAdmin();

        $event = $isAdmin
            ? $this->service->find($id)
            : $this->service->findVisibleToMembers($id);

        if ($event === null) {
            return $this->notFound();
        }

        $memberId = Auth::memberId();

        return $this->view(
            'events.show',
            [
                'title' => $event->titel,
                'event' => $event,
                'isAdmin' => $isAdmin,
                'registration' => !$isAdmin && $memberId !== null
                    ? $this->service->registrationForMember(
                        $id,
                        $memberId
                    )
                    : null,
                'registrations' => $isAdmin
                    ? $this->service->registrationsForEvent($id)
                    : [],
                'shifts' => $isAdmin
                    ? $this->service->shiftsForEvent($id)
                    : [],
            ]
        );
    }

    public function create(): Response
    {
        return $this->view(
            'events.create',
            [
                'title' => 'Nieuw evenement',
                'shiftTypes' => $this->service->activeShiftTypes(),
            ]
        );
    }

    public function store(): Response
    {
        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);

            $eventRequest = new EventRequest($input);
            $id = $this->service->create(
                $eventRequest->all(),
                $eventRequest->shifts()
            );

            $this->success(
                'Het evenement en de opgegeven shifts werden succesvol aangemaakt.'
            );

            return $this->redirect('/events/' . $id);
        } catch (Throwable $throwable) {
            $this->flashValidationFailure(
                $input,
                $throwable,
                'Het evenement kon niet worden aangemaakt.'
            );

            return $this->redirect('/events/create');
        }
    }

    public function edit(): Response
    {
        $id = $this->routeId();
        $event = $this->service->find($id);

        if ($event === null) {
            return $this->notFound();
        }

        return $this->view(
            'events.edit',
            [
                'title' => 'Evenement wijzigen',
                'event' => $event,
                'shiftTypes' => $this->service->activeShiftTypes(),
                'shifts' => $this->service->shiftsForEvent($id),
            ]
        );
    }

    public function update(): Response
    {
        $id = $this->routeId();
        $input = $this->request()->request->all();
        $event = $this->service->find($id);

        if ($event === null) {
            return $this->notFound();
        }

        try {
            $this->validateCsrf($input);

            $eventRequest = new EventRequest($input);
            $data = $eventRequest->all();

            $this->service->update(
                $id,
                $data,
                $eventRequest->shifts()
            );

            $this->success(
                ($data['status'] ?? null) === 'geannuleerd'
                    ? 'Het evenement werd geannuleerd. Betrokken leden worden per mail verwittigd; hun actieve inschrijvingen en shifts worden na succesvolle aflevering automatisch geannuleerd.'
                    : 'Het evenement en de opgegeven shifts werden succesvol gewijzigd.'
            );

            return $this->redirect('/events/' . $id);
        } catch (Throwable $throwable) {
            $this->flashValidationFailure(
                $input,
                $throwable,
                'Het evenement kon niet worden gewijzigd.'
            );

            return $this->redirect('/events/' . $id . '/edit');
        }
    }

    public function destroy(): Response
    {
        $id = $this->routeId();
        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);
            $this->service->delete($id);

            $this->success(
                'Het evenement werd succesvol verwijderd.'
            );
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());
        }

        return $this->redirect('/events');
    }

    public function register(): Response
    {
        $eventId = $this->routeId();
        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);

            $request = new EventRegistrationRequest($input);
            $data = $request->all();

            $this->service->registerMember(
                $eventId,
                $this->requireMemberId(),
                $data['dagen']
            );

            $this->success(
                'Je beschikbaarheid werd geregistreerd en wacht op beoordeling.'
            );
        } catch (Throwable $throwable) {
            $this->flashRegistrationFailure($input, $throwable);
        }

        return $this->redirect('/events/' . $eventId);
    }

    public function cancelRegistration(): Response
    {
        $eventId = $this->routeId();
        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);

            $request = new EventCancellationRequest($input);
            $data = $request->all();
            $requiresVerification = $this->service
                ->requestRegistrationCancellation(
                    $eventId,
                    $this->requireMemberId(),
                    $data['reden']
                );

            $this->success(
                $requiresVerification
                    ? 'Je annulatieaanvraag werd geregistreerd en wacht op verificatie door een administrator.'
                    : 'Je evenementinschrijving werd geannuleerd.'
            );
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());
        }

        return $this->redirect('/events/' . $eventId);
    }

    public function confirmRegistrationCancellation(): Response
    {
        $registrationId = $this->routeId('registrationId');
        $registration = $this->service->findRegistration($registrationId);

        if ($registration === null) {
            return $this->notFound(
                'Evenementinschrijving niet gevonden.'
            );
        }

        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);
            $cancelledAssignments = $this->service
                ->confirmRegistrationCancellation($registrationId);

            $message = 'De annulatieaanvraag werd bevestigd.';

            if ($cancelledAssignments > 0) {
                $message .= sprintf(
                    ' %d actieve shifttoewijzing(en) werden eveneens geannuleerd.',
                    $cancelledAssignments
                );
            }

            $this->success($message);
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());
        }

        return $this->redirect('/events/' . $registration->eventId);
    }

    public function approveRegistration(): Response
    {
        return $this->handleRegistrationDecision(
            static function (
                EventService $service,
                int $registrationId
            ): void {
                $service->approveRegistration($registrationId);
            },
            'De evenementinschrijving werd bevestigd.'
        );
    }

    public function reserveRegistration(): Response
    {
        return $this->handleRegistrationDecision(
            static function (
                EventService $service,
                int $registrationId
            ): void {
                $service->reserveRegistration($registrationId);
            },
            'De evenementinschrijving werd op reserve geplaatst.'
        );
    }

    public function rejectRegistration(): Response
    {
        return $this->handleRegistrationDecision(
            static function (
                EventService $service,
                int $registrationId
            ): void {
                $service->rejectRegistration($registrationId);
            },
            'De evenementinschrijving werd geweigerd.'
        );
    }

    /**
     * @param callable(EventService, int): void $decision
     */
    private function handleRegistrationDecision(
        callable $decision,
        string $successMessage
    ): Response {
        $registrationId = $this->routeId('registrationId');
        $registration = $this->service->findRegistration($registrationId);

        if ($registration === null) {
            return $this->notFound(
                'Evenementinschrijving niet gevonden.'
            );
        }

        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);
            $decision($this->service, $registrationId);
            $this->success($successMessage);
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());
        }

        return $this->redirect('/events/' . $registration->eventId);
    }

    /**
     * @param array<string, mixed> $input
     */
    private function validateCsrf(array $input): void
    {
        $token = $input['_token'] ?? null;

        if (
            !is_string($token)
            || !$this->csrf->validate($token)
        ) {
            throw new RuntimeException(
                'De beveiligingstoken is ongeldig of verlopen. Probeer opnieuw.'
            );
        }
    }

    /**
     * @param array<string, mixed> $input
     */
    private function flashValidationFailure(
        array $input,
        Throwable $throwable,
        string $flashMessage
    ): void {
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

        $this->error($flashMessage);
    }

    /**
     * @param array<string, mixed> $input
     */
    private function flashRegistrationFailure(
        array $input,
        Throwable $throwable
    ): void {
        unset($input['_token']);
        Session::flash('_old_input', $input);
        $this->error($throwable->getMessage());
    }

    private function requireMemberId(): int
    {
        $memberId = Auth::memberId();

        if ($memberId === null || $memberId <= 0) {
            throw new RuntimeException(
                'Aan dit gebruikersaccount is geen geldig lid gekoppeld.'
            );
        }

        return $memberId;
    }

    private function routeId(string $key = 'id'): int
    {
        return (int) $this->request()->route($key, 0);
    }

    private function notFound(
        string $message = 'Evenement niet gevonden.'
    ): Response {
        return $this->view(
            'core::errors.404',
            [
                'message' => $message,
            ],
            404
        );
    }
}
