<?php

declare(strict_types=1);

namespace App\Controllers;

use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;
use AEFS\Core\Session;
use AEFS\Core\View\Helper\CsrfHelper;
use AEFS\Core\View\ViewFactory;
use App\Http\Requests\MemberGroupAssignmentRequest;
use App\Http\Requests\MemberGroupRequest;
use App\Http\Requests\MemberRequest;
use App\Services\AuditLogService;
use App\Services\MemberGroupService;
use App\Services\MemberService;
use RuntimeException;
use Throwable;

final class MemberController extends BaseController
{
    public function __construct(
        ViewFactory $views,
        Request $request,
        private readonly MemberService $service,
        private readonly AuditLogService $auditLog,
        private readonly MemberGroupService $groups,
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

        $leden = $zoekterm === ''
            ? $this->service->all()
            : $this->service->search($zoekterm);

        return $this->view(
            'members.index',
            [
                'title' => 'Leden',
                'titel' => 'Leden',
                'zoekterm' => $zoekterm,
                'leden' => $leden,
            ]
        );
    }

    public function show(): Response
    {
        $id = $this->routeId();
        $lid = $this->service->find($id);

        if ($lid === null) {
            return $this->notFound();
        }

        return $this->view(
            'members.show',
            [
                'title' => $lid->fullName(),
                'titel' => 'Ledenfiche',
                'lid' => $lid,
                'logs' => $this->auditLog->history(
                    'member',
                    $id
                ),
                'groepen' => $this->groups->forMember($id),
            ]
        );
    }

    public function groups(): Response
    {
        $groups = $this->groups->all();
        $selectedGroupId = (int) $this->request()->query->get(
            'groep',
            $groups[0]->groepId ?? 0
        );

        $selectedGroup = $this->groups->find($selectedGroupId);

        return $this->view(
            'members.groups',
            [
                'title' => 'Ledengroepen',
                'groepen' => $groups,
                'geselecteerdeGroep' => $selectedGroup,
                'leden' => $this->service->all(),
                'geselecteerdeLidIds' => $selectedGroup !== null
                    ? $this->groups->memberIds(
                        $selectedGroup->groepId
                    )
                    : [],
                'groepPerLid' => $this->groups
                    ->membershipByMember(),
            ]
        );
    }

    public function createGroup(): Response
    {
        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);

            $groupRequest = new MemberGroupRequest($input);
            $groupId = $this->groups->create(
                $groupRequest->all()
            );

            $this->success(
                'De groep werd succesvol aangemaakt.'
            );

            return $this->redirect(
                '/members/groups?groep=' . $groupId
            );
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
                'De groep kon niet worden aangemaakt.'
            );

            return $this->redirect('/members/groups');
        }
    }

    public function updateGroupMembers(): Response
    {
        $groupId = $this->routeId();
        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);

            $assignmentRequest = new MemberGroupAssignmentRequest(
                $input
            );

            $this->groups->syncMembers(
                $groupId,
                $assignmentRequest->memberIds()
            );

            $this->success(
                'De groepsleden werden succesvol bijgewerkt.'
            );
        } catch (Throwable $throwable) {
            $this->error($throwable->getMessage());
        }

        return $this->redirect(
            '/members/groups?groep=' . $groupId
        );
    }

    public function edit(): Response
    {
        $id = $this->routeId();
        $lid = $this->service->find($id);

        if ($lid === null) {
            return $this->notFound();
        }

        return $this->view(
            'members.edit',
            [
                'title' => $lid->fullName(),
                'titel' => 'Lid wijzigen',
                'lid' => $lid,
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
            $memberRequest = new MemberRequest($input);

            $this->service->update(
                $id,
                $memberRequest->all()
            );

            $this->success(
                'Het lid werd succesvol gewijzigd.'
            );

            return $this->redirect(
                '/members/' . $id
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
                'Het lid kon niet worden gewijzigd.'
            );

            return $this->redirect(
                '/members/' . $id . '/edit'
            );
        }
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

        if (
            !is_string($token)
            || !$this->csrf->validate($token)
        ) {
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
                'message' => 'Lid niet gevonden.',
            ],
            404
        );
    }
}
