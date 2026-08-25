<?php

declare(strict_types=1);

namespace App\Controllers;

use AEFS\Core\Auth;
use AEFS\Core\Http\JsonResponse;
use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;
use AEFS\Core\Session;
use AEFS\Core\View\Helper\CsrfHelper;
use AEFS\Core\View\ViewFactory;
use App\Http\Requests\ShiftRegistrationRequest;
use App\Http\Requests\ShiftRequest;
use App\Repositories\EventRepository;
use App\Services\ShiftService;
use DomainException;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class ShiftController extends BaseController
{
    public function __construct(
        ViewFactory $views,
        Request $request,
        private readonly ShiftService $service,
        private readonly EventRepository $eventRepository,
        private readonly CsrfHelper $csrf
    ) {
        parent::__construct(
            $views,
            $request
        );
    }

    public function index(): Response
    {
        $isAdmin = Auth::isAdmin();
        $memberId = Auth::memberId();

        return $this->view(
            'shifts.index',
            [
                'title' => 'Shiftplanning',
                'isAdmin' => $isAdmin,
                'events' => $isAdmin
                    ? $this->eventRepository->allForAdministration()
                    : [],
                'shifts' => $isAdmin
                    ? $this->service->allForAdministration()
                    : [],
                'memberRegistrations' => $memberId !== null
                    ? $this->service->registrationsForMember(
                        $memberId
                    )
                    : [],
                'pendingRegistrations' => $isAdmin
                    ? $this->service->pendingRegistrations()
                    : [],
            ]
        );
    }

    public function planner(): Response
    {
        $eventId = $this->routeId('eventId');
        $event = $this->eventRepository->find($eventId);

        if ($event === null) {
            return $this->notFound(
                'Evenement niet gevonden.'
            );
        }

        return $this->view(
            'shifts.planner',
            [
                'title' => 'Shiftplanning · ' . $event->titel,
                'event' => $event,
                'shifts' => $this->service->findByEvent(
                    $eventId
                ),
                'shiftTypes' => $this->service->allTypes(),
            ]
        );
    }

    public function show(): Response
    {
        $shiftId = $this->routeId();
        $isAdmin = Auth::isAdmin();
        $memberId = Auth::memberId();
        $memberRegistration = !$isAdmin && $memberId !== null
            ? $this->service->findMemberRegistration(
                $shiftId,
                $memberId
            )
            : null;

        $shift = $isAdmin
            ? $this->service->find($shiftId)
            : ($memberRegistration !== null
                ? $this->service->find($shiftId)
                : null);

        if ($shift === null) {
            return $this->notFound(
                'Shift niet gevonden.'
            );
        }

        return $this->view(
            'shifts.show',
            [
                'title' => $shift->displayNaam(),
                'shift' => $shift,
                'isAdmin' => $isAdmin,
                'registrations' => $isAdmin
                    ? $this->service->registrationsForShift(
                        $shiftId
                    )
                    : [],
                'memberRegistration' => $memberRegistration,
                'eligibleEventRegistrations' => $isAdmin
                    ? $this->service
                        ->eligibleEventRegistrationsForShift($shiftId)
                    : [],
            ]
        );
    }

    public function create(): Response
    {
        $selectedEventId = (int) $this->request()
            ->query
            ->get(
                'event_id',
                0
            );

        return $this->view(
            'shifts.create',
            [
                'title' => 'Nieuwe shift',
                'events' => $this->eventRepository
                    ->allForAdministration(),
                'shiftTypes' => $this->service
                    ->activeTypes(),
                'selectedEventId' => $selectedEventId,
            ]
        );
    }

    public function store(): Response
    {
        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);

            $shiftRequest = new ShiftRequest($input);

            $id = $this->service->create(
                $shiftRequest->all()
            );

            $this->success(
                'De shift werd succesvol aangemaakt.'
            );

            return $this->redirect(
                '/shifts/' . $id
            );
        } catch (Throwable $throwable) {
            $this->flashValidationFailure(
                $input,
                $throwable,
                'De shift kon niet worden aangemaakt.'
            );

            return $this->redirect(
                '/shifts/create'
            );
        }
    }

    public function edit(): Response
    {
        $shiftId = $this->routeId();
        $shift = $this->service->find($shiftId);

        if ($shift === null) {
            return $this->notFound(
                'Shift niet gevonden.'
            );
        }

        return $this->view(
            'shifts.edit',
            [
                'title' => 'Shift wijzigen',
                'shift' => $shift,
                'events' => $this->eventRepository
                    ->allForAdministration(),
                'shiftTypes' => $this->service
                    ->allTypes(),
            ]
        );
    }

    public function update(): Response
    {
        $shiftId = $this->routeId();
        $input = $this->request()->request->all();
        $shift = $this->service->find($shiftId);

        if ($shift === null) {
            return $this->notFound(
                'Shift niet gevonden.'
            );
        }

        try {
            $this->validateCsrf($input);

            $shiftRequest = new ShiftRequest($input);

            $this->service->update(
                $shiftId,
                $shiftRequest->all()
            );

            $this->success(
                'De shift werd succesvol gewijzigd.'
            );

            return $this->redirect(
                '/shifts/' . $shiftId
            );
        } catch (Throwable $throwable) {
            $this->flashValidationFailure(
                $input,
                $throwable,
                'De shift kon niet worden gewijzigd.'
            );

            return $this->redirect(
                '/shifts/' . $shiftId . '/edit'
            );
        }
    }

    public function cancelShift(): Response
    {
        $shiftId = $this->routeId();
        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);

            $request = new ShiftRegistrationRequest(
                $input
            );

            $data = $request->all();

            $cancelledRegistrations = $this->service
                ->cancelShift(
                    $shiftId,
                    $data['annulatie_reden']
                );

            $this->success(
                sprintf(
                    'De shift werd geannuleerd. %d actieve inschrijving(en) werden eveneens geannuleerd.',
                    $cancelledRegistrations
                )
            );
        } catch (Throwable $throwable) {
            $this->error(
                $throwable->getMessage()
            );
        }

        return $this->redirect(
            '/shifts/' . $shiftId
        );
    }

    public function destroy(): Response
    {
        $shiftId = $this->routeId();
        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);

            $this->service->delete($shiftId);

            $this->success(
                'De shift werd succesvol verwijderd.'
            );

            return $this->redirect('/shifts');
        } catch (Throwable $throwable) {
            $this->error(
                $throwable->getMessage()
            );

            return $this->redirect(
                '/shifts/' . $shiftId
            );
        }
    }

    public function assign(): Response
    {
        $shiftId = $this->routeId();
        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);

            $request = new ShiftRegistrationRequest(
                $input
            );

            $data = $request->all();

            $this->service->assignByAdmin(
                shiftId: $shiftId,
                memberId: $data['lid_id'],
                status: $data['status']
            );

            $this->success(
                'De vrijwilliger werd aan de shift toegewezen.'
            );
        } catch (Throwable $throwable) {
            $this->error(
                $throwable->getMessage()
            );
        }

        return $this->redirect(
            '/shifts/' . $shiftId
        );
    }

    public function approve(): Response
    {
        return $this->handleDecision(
            static function (
                ShiftService $service,
                int $registrationId
            ): void {
                $service->approve(
                    $registrationId
                );
            },
            'De inschrijving werd goedgekeurd.'
        );
    }

    public function reserve(): Response
    {
        return $this->handleDecision(
            static function (
                ShiftService $service,
                int $registrationId
            ): void {
                $service->reserve(
                    $registrationId
                );
            },
            'De inschrijving werd op de reservelijst geplaatst.'
        );
    }

    public function reject(): Response
    {
        return $this->handleDecision(
            static function (
                ShiftService $service,
                int $registrationId
            ): void {
                $service->reject(
                    $registrationId
                );
            },
            'De inschrijving werd geweigerd.'
        );
    }

    public function cancelRegistration(): Response
    {
        $registrationId = $this->routeId(
            'registrationId'
        );

        $registration = $this->service
            ->findRegistration(
                $registrationId
            );

        if ($registration === null) {
            return $this->notFound(
                'Shiftinschrijving niet gevonden.'
            );
        }

        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);

            $request = new ShiftRegistrationRequest(
                $input
            );

            $data = $request->all();

            $this->service->cancelByAdmin(
                $registrationId,
                $data['annulatie_reden']
            );

            $this->success(
                'De shiftinschrijving werd geannuleerd.'
            );
        } catch (Throwable $throwable) {
            $this->error(
                $throwable->getMessage()
            );
        }

        return $this->redirect(
            '/shifts/' . $registration->shiftId
        );
    }

    public function presence(): Response
    {
        $expectsJson = $this->request()->isAjax()
            || $this->request()->acceptsJson();

        $registrationId = $this->routeId(
            'registrationId'
        );

        $registration = $this->service
            ->findRegistration(
                $registrationId
            );

        if ($registration === null) {
            if ($expectsJson) {
                return new JsonResponse(
                    [
                        'success' => false,
                        'present' => false,
                        'message' => 'Shiftinschrijving niet gevonden.',
                    ],
                    404
                );
            }

            return $this->notFound(
                'Shiftinschrijving niet gevonden.'
            );
        }

        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);
        } catch (Throwable $throwable) {
            if ($expectsJson) {
                return new JsonResponse(
                    [
                        'success' => false,
                        'present' => $registration->aanwezig,
                        'message' => $throwable->getMessage(),
                    ],
                    419
                );
            }

            $this->error(
                $throwable->getMessage()
            );

            return $this->redirect(
                '/shifts/' . $registration->shiftId
            );
        }

        try {
            $request = new ShiftRegistrationRequest(
                $input
            );

            $data = $request->all();

            $this->service->setPresence(
                $registrationId,
                $data['aanwezig']
            );

            $message = $data['aanwezig']
                ? 'De vrijwilliger werd als aanwezig gemarkeerd.'
                : 'De aanwezigheidsmarkering werd verwijderd.';

            if ($expectsJson) {
                return new JsonResponse(
                    [
                        'success' => true,
                        'present' => $data['aanwezig'],
                        'message' => $message,
                    ]
                );
            }

            $this->success($message);
        } catch (Throwable $throwable) {
            if ($expectsJson) {
                $isExpectedFailure = $throwable instanceof DomainException
                    || $throwable instanceof InvalidArgumentException;

                return new JsonResponse(
                    [
                        'success' => false,
                        'present' => $registration->aanwezig,
                        'message' => $isExpectedFailure
                            ? $throwable->getMessage()
                            : 'De aanwezigheidsstatus kon niet worden bijgewerkt.',
                    ],
                    $throwable instanceof InvalidArgumentException
                        ? 404
                        : ($isExpectedFailure ? 422 : 500)
                );
            }

            $this->error(
                $throwable->getMessage()
            );
        }

        return $this->redirect(
            '/shifts/' . $registration->shiftId
        );
    }

    /**
     * @param callable(ShiftService, int): void $decision
     */
    private function handleDecision(
        callable $decision,
        string $successMessage
    ): Response {
        $registrationId = $this->routeId(
            'registrationId'
        );

        $registration = $this->service
            ->findRegistration(
                $registrationId
            );

        if ($registration === null) {
            return $this->notFound(
                'Shiftinschrijving niet gevonden.'
            );
        }

        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);

            $decision(
                $this->service,
                $registrationId
            );

            $this->success($successMessage);
        } catch (Throwable $throwable) {
            $this->error(
                $throwable->getMessage()
            );
        }

        return $this->redirect(
            '/shifts/' . $registration->shiftId
        );
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

        Session::flash(
            '_old_input',
            $input
        );

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

    private function routeId(
        string $key = 'id'
    ): int {
        return (int) $this->request()->route(
            $key,
            0
        );
    }

    private function notFound(
        string $message
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
