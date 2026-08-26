<?php

declare(strict_types=1);

namespace App\Controllers;

use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;
use AEFS\Core\Http\JsonResponse;
use AEFS\Core\View\Helper\CsrfHelper;
use AEFS\Core\View\ViewFactory;
use App\Services\ReportService;

final class ReportController extends BaseController
{
    public function __construct(
        ViewFactory $views,
        Request $request,
        private readonly ReportService $service,
        private readonly CsrfHelper $csrf
    ) {
        parent::__construct($views, $request);
    }

    public function index(): Response
    {
        return $this->view('reports.index', ['title' => 'Rapporten']);
    }

    public function shiftAttendance(): Response
    {
        $shiftId = max(0, (int) $this->request()->query->get('shift_id', 0));
        $report = $shiftId > 0
            ? $this->service->shiftAttendance($shiftId)
            : null;

        if ($shiftId > 0 && $report === null) {
            return $this->view(
                'core::errors.404',
                [
                    'title' => 'Shift niet gevonden',
                    'message' => 'De gekozen shift bestaat niet.',
                ],
                404
            );
        }

        return $this->view(
            'reports.shift-attendance',
            [
                'title' => 'Aanwezigheidslijst per shift',
                'shifts' => $this->service->shiftsForAttendance(),
                'selectedShiftId' => $shiftId,
                'shift' => $report['shift'] ?? null,
                'registrations' => $report['registrations'] ?? [],
            ]
        );
    }

    public function dayAttendance(): Response
    {
        $date = trim((string) $this->request()->query->get('date', ''));
        $report = $date !== ''
            ? $this->service->dayAttendance($date)
            : null;

        if ($date !== '' && $report === null) {
            return $this->view(
                'core::errors.404',
                [
                    'title' => 'Eventdag niet gevonden',
                    'message' => 'Voor de gekozen datum zijn geen shifts beschikbaar.',
                ],
                404
            );
        }

        return $this->view(
            'reports.day-attendance',
            [
                'title' => 'Aanwezigheidslijst per dag',
                'days' => $this->service->daysForAttendance(),
                'selectedDate' => $date,
                'report' => $report,
            ]
        );
    }

    public function saveDayAttendanceDetails(): Response
    {
        $input = $this->request()->request->all();
        $expectsJson = $this->request()->isAjax() || $this->request()->acceptsJson();
        $date = trim((string) ($input['date'] ?? ''));
        $memberId = (int) ($input['member_id'] ?? 0);

        try {
            $token = $input['_token'] ?? null;

            if (!is_string($token) || !$this->csrf->validate($token)) {
                throw new \RuntimeException('De beveiligingstoken is ongeldig of verlopen.');
            }

            $this->service->saveDayDetails(
                $date,
                $memberId,
                (string) ($input['nummer_walkie'] ?? ''),
                filter_var($input['oortje'] ?? false, FILTER_VALIDATE_BOOL)
            );

            if ($expectsJson) {
                return JsonResponse::success(message: 'Daggegevens opgeslagen.');
            }

            $this->success('De daggegevens werden opgeslagen.');
        } catch (\Throwable $throwable) {
            if ($expectsJson) {
                return JsonResponse::error(message: $throwable->getMessage(), statusCode: 422);
            }

            $this->error($throwable->getMessage());
        }

        return $this->redirect('/reports/day-attendance?date=' . rawurlencode($date));
    }

    public function eventParticipants(): Response
    {
        $eventId = max(0, (int) $this->request()->query->get('event_id', 0));
        $report = $eventId > 0
            ? $this->service->eventParticipants($eventId)
            : null;

        if ($eventId > 0 && $report === null) {
            return $this->view(
                'core::errors.404',
                [
                    'title' => 'Evenement niet gevonden',
                    'message' => 'Het gekozen evenement bestaat niet.',
                ],
                404
            );
        }

        return $this->view(
            'reports.event-participants',
            [
                'title' => 'Ingeschreven leden per evenement',
                'events' => $this->service->eventsForParticipantList(),
                'selectedEventId' => $eventId,
                'event' => $report['event'] ?? null,
                'registrations' => $report['registrations'] ?? [],
            ]
        );
    }
}
