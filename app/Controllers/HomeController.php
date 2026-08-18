<?php

declare(strict_types=1);

namespace App\Controllers;

use AEFS\Core\Auth;
use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;
use AEFS\Core\View\ViewFactory;

final class HomeController extends BaseController
{
    public function __construct(
        ViewFactory $views,
        Request $request
    ) {
        parent::__construct(
            $views,
            $request
        );
    }

    public function index(): Response
    {
        return $this->redirect(
            Auth::check()
                ? '/dashboard'
                : '/login'
        );
    }
}
