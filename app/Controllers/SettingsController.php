<?php

declare(strict_types=1);

namespace App\Controllers;

use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;
use AEFS\Core\Session;
use AEFS\Core\View\Helper\CsrfHelper;
use AEFS\Core\View\ViewFactory;
use App\Http\Requests\SettingsRequest;
use App\Http\Requests\ShiftTypeRequest;
use App\Services\SettingsService;
use RuntimeException;
use Throwable;

final class SettingsController extends BaseController
{
    public function __construct(
        ViewFactory $views,
        Request $request,
        private readonly SettingsService $service,
        private readonly CsrfHelper $csrf
    ) {
        parent::__construct($views, $request);
    }

    public function index(): Response
    {
        return $this->view(
            'settings.index',
            [
                'title' => 'Instellingen',
                'settings' => $this->service->all(),
                'shiftTypes' => $this->service->shiftTypeOverview(),
                'status' => $this->service->operationalStatus(),
            ]
        );
    }

    public function update(): Response
    {
        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);
            $request = new SettingsRequest($input);
            $this->service->update($request->all());
            $this->success('De algemene instellingen werden opgeslagen.');
        } catch (Throwable $throwable) {
            $this->flashFailure(
                $input,
                $throwable,
                'De instellingen konden niet worden opgeslagen.'
            );
        }

        return $this->redirect('/settings#general-settings');
    }

    public function storeShiftType(): Response
    {
        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);
            $request = new ShiftTypeRequest($input);
            $this->service->createShiftType($request->all());
            $this->success('De shiftfunctie werd aangemaakt.');
        } catch (Throwable $throwable) {
            $this->flashFailure(
                $input,
                $throwable,
                'De shiftfunctie kon niet worden aangemaakt.'
            );
        }

        return $this->redirect('/settings#shift-types');
    }

    public function updateShiftType(): Response
    {
        $id = (int) $this->request()->route('id', 0);
        $input = $this->request()->request->all();

        try {
            $this->validateCsrf($input);
            $request = new ShiftTypeRequest($input);
            $this->service->updateShiftType($id, $request->all());
            $this->success('De shiftfunctie werd bijgewerkt.');
        } catch (Throwable $throwable) {
            $this->flashFailure(
                $input,
                $throwable,
                'De shiftfunctie kon niet worden bijgewerkt.'
            );
        }

        return $this->redirect('/settings#shift-type-' . $id);
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

    /**
     * @param array<string, mixed> $input
     */
    private function flashFailure(
        array $input,
        Throwable $throwable,
        string $message
    ): void {
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
        $this->error($message);
    }
}
