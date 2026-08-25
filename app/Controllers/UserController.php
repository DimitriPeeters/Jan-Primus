<?php

declare(strict_types=1);

namespace App\Controllers;

use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;
use AEFS\Core\Session;
use AEFS\Core\View\Helper\CsrfHelper;
use AEFS\Core\View\ViewFactory;
use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\UserService;
use RuntimeException;
use Throwable;

final class UserController extends BaseController
{
    public function __construct(
        ViewFactory $views,
        Request $request,
        private readonly UserService $users,
        private readonly AuditLogService $auditLog,
        private readonly CsrfHelper $csrf
    ) {
        parent::__construct(
            $views,
            $request
        );
    }

    public function index(): Response
    {
        $search = trim(
            (string) $this->request()->query->get(
                'zoek',
                ''
            )
        );

        $allUsers = $this->users->all();

        $visibleUsers = $search === ''
            ? $allUsers
            : $this->users->search($search);

        $pendingUsers = array_values(
            array_filter(
                $visibleUsers,
                static fn (User $user): bool => $user->isPending()
            )
        );

        $approvedUsers = array_values(
            array_filter(
                $visibleUsers,
                static fn (User $user): bool => !$user->isPending()
            )
        );

        return $this->view(
            'users.index',
            [
                'title' => 'Gebruikers',
                'zoekterm' => $search,
                'gebruikers' => $visibleUsers,
                'wachtendeGebruikers' => $pendingUsers,
                'goedgekeurdeGebruikers' => $approvedUsers,
                'statistieken' => [
                    'wachtend' => $this->countUsers(
                        $allUsers,
                        static fn (User $user): bool => $user->isPending()
                    ),
                    'actief' => $this->countUsers(
                        $allUsers,
                        static fn (User $user): bool => $user->isApproved()
                            && $user->isActive()
                    ),
                    'inactief' => $this->countUsers(
                        $allUsers,
                        static fn (User $user): bool => $user->isInactive()
                    ),
                ],
            ]
        );
    }

    public function show(): Response
    {
        $id = $this->routeId();
        $user = $this->users->find($id);

        if ($user === null) {
            return $this->notFound();
        }

        return $this->view(
            'users.show',
            [
                'title' => $user->fullName(),
                'gebruiker' => $user,
                'logs' => $this->auditLog->history(
                    'user',
                    $id
                ),
            ]
        );
    }

    public function edit(): Response
    {
        $id = $this->routeId();
        $user = $this->users->find($id);

        if ($user === null) {
            return $this->notFound();
        }

        return $this->view(
            'users.edit',
            [
                'title' => 'Account goedkeuren en rol beheren',
                'gebruiker' => $user,
            ]
        );
    }

    public function update(): Response
    {
        $id = $this->routeId();
        $input = $this->request()->request->all();

        Session::flash(
            '_old_input',
            $input
        );

        try {
            $this->validateCsrf($input);
            $request = new UserRequest($input);

            $this->users->update(
                $id,
                $request->all()
            );

            $this->success(
                'De goedkeuring en rol werden succesvol opgeslagen.'
            );

            return $this->redirect(
                '/users/' . $id
            );
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
                'De accountinstellingen konden niet worden opgeslagen.'
            );

            return $this->redirect(
                '/users/' . $id . '/edit'
            );
        }
    }

    public function approve(): Response
    {
        $id = $this->routeId();
        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);
            $this->users->approve($id);

            $this->success(
                'De registratie werd goedgekeurd. Het account is nu actief.'
            );
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());
        }

        return $this->redirect('/users');
    }

    public function reject(): Response
    {
        $id = $this->routeId();
        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);
            $this->users->reject($id);

            $this->success(
                'De registratie werd afgekeurd en de persoonsgegevens werden verwijderd.'
            );
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());
        }

        return $this->redirect('/users');
    }

    /**
     * @param User[] $users
     * @param callable(User): bool $condition
     */
    private function countUsers(
        array $users,
        callable $condition
    ): int {
        return count(
            array_filter(
                $users,
                $condition
            )
        );
    }

    private function routeId(): int
    {
        return (int) $this->request()->route(
            'id',
            0
        );
    }

    /**
     * @param array<string, mixed> $input
     */
    private function validateCsrf(array $input): void
    {
        $token = $input['_token'] ?? null;

        if (!is_string($token) || !$this->csrf->validate($token)) {
            throw new RuntimeException(
                'De beveiligingstoken is ongeldig of verlopen. Probeer opnieuw.'
            );
        }
    }

    private function notFound(): Response
    {
        return $this->view(
            'core::errors.404',
            [
                'message' => 'Gebruiker niet gevonden.',
            ],
            404
        );
    }
}
