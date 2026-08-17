<?php

declare(strict_types=1);

namespace App\Controllers;

use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;
use AEFS\Core\Session;
use AEFS\Core\View\ViewFactory;
use App\Http\Requests\RegistrationRequest;
use App\Services\RegistrationService;
use Throwable;

final class RegistrationController extends BaseController
{
    public function __construct(
        ViewFactory $views,
        Request $request,
        private readonly RegistrationService $registrations
    ) {
        parent::__construct(
            $views,
            $request
        );
    }

    public function create(): Response
    {
        return $this->view(
            'auth.register',
            [
                'title' => 'Registreren',
            ]
        );
    }

    public function store(): Response
    {
        $input = $this->request()->request->all();
        $oldInput = $input;

        unset(
            $oldInput['password'],
            $oldInput['password_confirmation']
        );

        Session::flash(
            '_old_input',
            $oldInput
        );

        try {
            $request = new RegistrationRequest($input);

            $this->registrations->register(
                $request->all()
            );

            $this->success(
                'Je registratie werd ontvangen. Een administrator moet je account nog goedkeuren voordat je kunt aanmelden.'
            );

            return $this->redirect('/login');
        } catch (Throwable $throwable) {
            Session::flash(
                '_errors',
                [
                    'form' => [
                        $throwable->getMessage(),
                    ],
                ]
            );

            $this->error(
                'De registratie kon niet worden opgeslagen.'
            );

            return $this->redirect('/register');
        }
    }
}