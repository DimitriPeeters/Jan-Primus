<?php

declare(strict_types=1);

namespace App\Controllers;

use AEFS\Core\Auth;
use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;
use AEFS\Core\Session;
use AEFS\Core\View\ViewFactory;
use App\Http\Requests\MemberRequest;
use App\Models\Member;
use App\Services\MemberService;
use Throwable;

final class ProfileController extends BaseController
{
    public function __construct(
        ViewFactory $views,
        Request $request,
        private readonly MemberService $members
    ) {
        parent::__construct(
            $views,
            $request
        );
    }

    public function show(): Response
    {
        $member = $this->currentMember();

        if ($member === null) {
            return $this->memberNotFound();
        }

        return $this->view(
            'profile.show',
            [
                'title' => 'Mijn profiel',
                'lid' => $member,
            ]
        );
    }

    public function edit(): Response
    {
        $member = $this->currentMember();

        if ($member === null) {
            return $this->memberNotFound();
        }

        return $this->view(
            'profile.edit',
            [
                'title' => 'Mijn profiel wijzigen',
                'lid' => $member,
            ]
        );
    }

    public function update(): Response
    {
        $member = $this->currentMember();

        if ($member === null) {
            return $this->memberNotFound();
        }

        $input = $this->request()->request->all();

        Session::flash(
            '_old_input',
            $input
        );

        try {
            $input = $this->preserveManagedFields(
                $member,
                $input
            );

            $memberRequest = new MemberRequest($input);

            $this->members->update(
                $member->lidId,
                $memberRequest->all()
            );

            $this->success(
                'Je profiel werd succesvol gewijzigd.'
            );

            return $this->redirect('/profile');
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
                'Je profiel kon niet worden gewijzigd.'
            );

            return $this->redirect('/profile/edit');
        }
    }

    private function currentMember(): ?Member
    {
        $memberId = Auth::memberId();

        if ($memberId === null || $memberId <= 0) {
            return null;
        }

        return $this->members->find($memberId);
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    private function preserveManagedFields(
        Member $member,
        array $input
    ): array {
        $input['actief'] = $member->actief
            ? '1'
            : '0';

        $input['gdpr_consent'] = $member->gdprConsent
            ? '1'
            : '0';

        $input['opmerkingen'] = $member->opmerkingen ?? '';

        return $input;
    }

    private function memberNotFound(): Response
    {
        return $this->view(
            'core::errors.404',
            [
                'message' => 'Aan dit gebruikersaccount is geen geldig ledenprofiel gekoppeld.',
            ],
            404
        );
    }
}
