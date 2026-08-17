<?php

declare(strict_types=1);

namespace App\Services;

use AEFS\Core\Auth;
use AEFS\Core\Database;
use App\Repositories\UserRepository;
use App\Validators\PasswordResetValidator;
use DomainException;

final class AuthenticationService
{
    public function __construct(
        private readonly Database $database,
        private readonly UserRepository $users,
        private readonly MailService $mail,
        private readonly PasswordResetValidator $passwordResetValidator
    ) {
    }

    public function check(): bool
    {
        return Auth::check();
    }

    public function attempt(
        string $email,
        string $password
    ): bool {
        $email = strtolower(trim($email));

        if ($email === '' || $password === '') {
            return false;
        }

        $user = $this->users->findByEmail($email);

        if ($user === null) {
            return false;
        }

        if (!$user->isApproved() || !$user->isActive()) {
            return false;
        }

        if (
            $user->passwordHash === ''
            || !password_verify(
                $password,
                $user->passwordHash
            )
        ) {
            return false;
        }

        Auth::login($user);

        return true;
    }

    public function logout(): void
    {
        Auth::logout();
    }

    public function requestPasswordReset(string $email): void
    {
        $email = strtolower(trim($email));
        $this->passwordResetValidator->validateEmail($email);
        $user = $this->users->findByEmail($email);

        if (
            $user === null
            || !$user->isApproved()
            || !$user->isActive()
        ) {
            return;
        }

        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);

        $this->database->transaction(
            function () use ($user, $token, $tokenHash): void {
                if (
                    !$this->users->issuePasswordResetToken(
                        $user->gebruikerId,
                        $tokenHash
                    )
                ) {
                    return;
                }

                $mailingId = $this->mail->queuePasswordReset(
                    $user,
                    $token
                );

                if ($mailingId === null) {
                    $this->users->clearPasswordResetToken(
                        $user->gebruikerId,
                        $tokenHash
                    );
                }
            }
        );
    }

    public function hasValidPasswordResetToken(string $token): bool
    {
        $tokenHash = $this->passwordResetTokenHash($token);

        return $tokenHash !== null
            && $this->users->hasValidPasswordResetToken($tokenHash);
    }

    public function resetPassword(
        string $token,
        string $password,
        string $confirmation
    ): void {
        $this->passwordResetValidator->validatePasswords(
            $password,
            $confirmation
        );
        $tokenHash = $this->passwordResetTokenHash($token);

        if ($tokenHash === null) {
            throw new DomainException(
                'Deze herstelkoppeling is ongeldig of verlopen.'
            );
        }

        $updated = $this->users->resetPasswordWithToken(
            $tokenHash,
            password_hash($password, PASSWORD_DEFAULT)
        );

        if (!$updated) {
            throw new DomainException(
                'Deze herstelkoppeling is ongeldig of verlopen.'
            );
        }
    }

    private function passwordResetTokenHash(string $token): ?string
    {
        $token = strtolower(trim($token));

        if (
            strlen($token) !== 64
            || !ctype_xdigit($token)
        ) {
            return null;
        }

        return hash('sha256', $token);
    }
}
