<?php

declare(strict_types=1);

namespace App\Controllers;

use AEFS\Core\Http\RedirectResponse;
use AEFS\Core\Http\Request;
use AEFS\Core\Http\Response;
use AEFS\Core\Session;
use AEFS\Core\View\Helper\CsrfHelper;
use AEFS\Core\View\ViewFactory;
use App\Http\Requests\PasswordResetRequest;
use App\Services\AuthenticationService;
use DomainException;
use InvalidArgumentException;
use Throwable;

final class AuthController extends BaseController
{
    public function __construct(
        ViewFactory $views,
        Request $request,
        private readonly AuthenticationService $auth,
        private readonly CsrfHelper $csrf
    ) {
        parent::__construct(
            $views,
            $request
        );
    }

    public function login(): Response
    {
        if ($this->auth->check()) {
            return $this->redirect('/dashboard');
        }

        return $this->view('auth.login');
    }

    public function authenticate(): Response
    {
        $email = trim(
            (string) $this->post('email', '')
        );

        $password = (string) $this->post(
            'password',
            ''
        );

        $remember = (string) $this->post(
            'remember',
            ''
        );

        Session::flash('_old_input', [
            'email' => $email,
            'remember' => $remember,
        ]);

        if ($email === '' || $password === '') {
            Session::flash('_errors', [
                'email' => $email === ''
                    ? ['E-mailadres is verplicht.']
                    : [],
                'password' => $password === ''
                    ? ['Wachtwoord is verplicht.']
                    : [],
            ]);

            $this->error(
                'Vul alle verplichte velden in.'
            );

            return $this->redirect('/login');
        }

        if (!$this->auth->attempt($email, $password)) {
            Session::flash('_errors', [
                'email' => [
                    'De combinatie van e-mailadres en wachtwoord is ongeldig.',
                ],
            ]);

            $this->error(
                'Aanmelden is mislukt.'
            );

            return $this->redirect('/login');
        }

        return $this->redirect('/dashboard');
    }

    public function logout(): RedirectResponse
    {
        $this->auth->logout();

        $this->success(
            'Je bent succesvol afgemeld.'
        );

        return $this->redirect('/login');
    }

    public function forgotPassword(): Response
    {
        return $this->view(
            'auth.forgot-password',
            [
                'title' => 'Wachtwoord vergeten',
            ]
        );
    }

    public function sendPasswordResetLink(): Response
    {
        $input = $this->request()->request->all();
        $request = new PasswordResetRequest($input);
        $email = $request->email();

        Session::flash('_old_input', [
            'email' => $email,
        ]);

        try {
            $this->validateCsrf($input);
            $this->auth->requestPasswordReset($email);

            $this->success(
                'Als dit e-mailadres bij een actief account hoort, ontvang je binnenkort een herstelmail.'
            );

            return $this->redirect('/forgot-password');
        } catch (InvalidArgumentException $exception) {
            Session::flash('_errors', [
                'email' => [
                    $exception->getMessage(),
                ],
            ]);
            $this->error(
                'Controleer het ingevulde e-mailadres.'
            );
        } catch (Throwable) {
            Session::flash('_errors', [
                'form' => [
                    'De aanvraag kon momenteel niet worden verwerkt. Probeer het later opnieuw.',
                ],
            ]);
            $this->error(
                'De herstelmail kon niet worden aangevraagd.'
            );
        }

        return $this->redirect('/forgot-password');
    }

    public function resetPassword(): Response
    {
        $token = (string) $this->request()->route('token', '');
        $tokenValid = $this->auth->hasValidPasswordResetToken($token);

        return $this->view(
            'auth.reset-password',
            [
                'title' => 'Nieuw wachtwoord instellen',
                'token' => $token,
                'tokenValid' => $tokenValid,
            ],
            $tokenValid ? 200 : 410
        );
    }

    public function updatePassword(): Response
    {
        $token = (string) $this->request()->route('token', '');
        $input = $this->request()->request->all();
        $request = new PasswordResetRequest($input);
        $passwords = $request->passwords();

        try {
            $this->validateCsrf($input);
            $this->auth->resetPassword(
                $token,
                $passwords['password'],
                $passwords['password_confirmation']
            );

            $this->success(
                'Je wachtwoord werd gewijzigd. Je kunt nu aanmelden.'
            );

            return $this->redirect('/login');
        } catch (InvalidArgumentException $exception) {
            Session::flash('_errors', [
                'form' => [
                    $exception->getMessage(),
                ],
            ]);
            $this->error(
                'Het nieuwe wachtwoord kon niet worden opgeslagen.'
            );

            return $this->redirect(
                '/reset-password/' . rawurlencode($token)
            );
        } catch (DomainException $exception) {
            $this->error($exception->getMessage());

            return $this->redirect('/forgot-password');
        } catch (Throwable) {
            $this->error(
                'Het nieuwe wachtwoord kon momenteel niet worden opgeslagen.'
            );

            return $this->redirect(
                '/reset-password/' . rawurlencode($token)
            );
        }
    }

    /**
     * @param array<string, mixed> $input
     */
    private function validateCsrf(array $input): void
    {
        $token = $input['_token'] ?? null;

        if (!is_string($token) || !$this->csrf->validate($token)) {
            throw new DomainException(
                'Ongeldige of verlopen beveiligingstoken.'
            );
        }
    }
}
