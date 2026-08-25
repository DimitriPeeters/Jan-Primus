<?php

declare(strict_types=1);

namespace App\Controllers;

use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;
use AEFS\Core\View\ViewFactory;
use App\Services\ReportService;

final class ReportController extends BaseController
{
    public function __construct(
        ViewFactory $views,
        Request $request,
        private readonly ReportService $service
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
}
