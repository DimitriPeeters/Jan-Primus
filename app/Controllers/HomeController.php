<?php

declare(strict_types=1);

namespace App\Controllers;

use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;
use AEFS\Core\View\ViewFactory;
use App\Repositories\HomeRepository;

final class HomeController extends BaseController
{
    public function __construct(
        ViewFactory $views,
        Request $request,
        private readonly HomeRepository $repository
    ) {
        parent::__construct(
            $views,
            $request
        );
    }

    public function index(): Response
    {
        return $this->view(
            'home',
            [
                'title' => 'AEFS v2',
                'titel' => 'AEFS v2',
                'versie' => $this->repository->databaseVersion(),
            ]
        );
    }
}