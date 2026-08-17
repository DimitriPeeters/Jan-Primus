<?php

declare(strict_types=1);

namespace App\Controllers;

use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;
use AEFS\Core\View\ViewFactory;
use App\Services\DashboardService;

final class DashboardController extends BaseController
{
    public function __construct(
        ViewFactory $views,
        Request $request,
        private readonly DashboardService $dashboardService
    ) {
        parent::__construct(
            $views,
            $request
        );
    }

    public function index(): Response
    {
        return $this->view(
            'dashboard.index',
            $this->dashboardService->getDashboardData()
        );
    }
}