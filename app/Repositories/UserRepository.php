<?php

declare(strict_types=1);

namespace App\Repositories;

use AEFS\Core\BaseRepository;
use AEFS\Core\Database;
use App\Models\User;
use PDO;

final class UserRepository extends BaseRepository
{
    protected string $table = 'gebruikers';

    protected string $primaryKey = 'gebruiker_id';

    public function __construct(
        Database $database
    ) {
        parent::__construct($database);
    }

    /**
     * @return User[]
     */
    public function all(
        string $orderBy = '',
        string $direction = 'ASC'
    ): array {
        unset($orderBy, $direction);

        $statement = $this->database->prepare(
            '
            SELECT
                g.*,
                l.voornaam,
                l.achternaam
            FROM gebruikers g
            INNER JOIN leden l
                ON l.lid_id = g.lid_id
            ORDER BY
                CASE
                    WHEN g.goedkeuringsstatus = :pending_status THEN 0
                    ELSE 1
                END ASC,
                g.actief ASC,
                l.voornaam ASC,
                l.achternaam ASC
            '
        );

        $statement->execute([
            'pending_status' => User::APPROVAL_PENDING,
        ]);

        return array_map(
            fn (array $row): User => $this->map($row),
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    /**
     * @return User[]
     */
    public function search(string $search): array
    {
        $search = trim($search);

        if ($search === '') {
            return $this->all();
        }

        $statement = $this->database->prepare(
            '
            SELECT
                g.*,
                l.voornaam,
                l.achternaam
            FROM gebruikers g
            INNER JOIN leden l
                ON l.lid_id = g.lid_id
            WHERE l.voornaam LIKE :search_first_name
               OR l.achternaam LIKE :search_last_name
               OR g.email LIKE :search_email
            ORDER BY
                CASE
                    WHEN g.goedkeuringsstatus = :pending_status THEN 0
                    ELSE 1
                END ASC,
                g.actief ASC,
                l.voornaam ASC,
                l.achternaam ASC
            '
        );

        $value = '%' . $search . '%';

        $statement->execute([
            'search_first_name' => $value,
            'search_last_name' => $value,
            'search_email' => $value,
            'pending_status' => User::APPROVAL_PENDING,
        ]);

        return array_map(
            fn (array $row): User => $this->map($row),
            $statement->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function find(int $id): ?User
    {
        if ($id <= 0) {
            return null;
        }

        $statement = $this->database->prepare(
            '
            SELECT
                g.*,
                l.voornaam,
                l.achternaam
            FROM gebruikers g
            INNER JOIN leden l
                ON l.lid_id = g.lid_id
            WHERE g.gebruiker_id = :id
            LIMIT 1
            '
        );

        $statement->execute([
            'id' => $id,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row)
            ? $this->map($row)
            : null;
    }

    public function findByEmail(string $email): ?User
    {
        $email = strtolower(trim($email));

        if ($email === '') {
            return null;
        }

        $statement = $this->database->prepare(
            '
            SELECT
                g.*,
                l.voornaam,
                l.achternaam
            FROM gebruikers g
            INNER JOIN leden l
                ON l.lid_id = g.lid_id
            WHERE LOWER(g.email) = :email
            LIMIT 1
            '
        );

        $statement->execute([
            'email' => $email,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row)
            ? $this->map($row)
            : null;
    }

    public function findByMemberId(int $memberId): ?User
    {
        if ($memberId <= 0) {
            return null;
        }

        $statement = $this->database->prepare(
            '
            SELECT
                g.*,
                l.voornaam,
                l.achternaam
            FROM gebruikers g
            INNER JOIN leden l
                ON l.lid_id = g.lid_id
            WHERE g.lid_id = :member_id
            LIMIT 1
            '
        );

        $statement->execute([
            'member_id' => $memberId,
        ]);

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row)
            ? $this->map($row)
            : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int
    {
        $statement = $this->database->prepare(
            '
            INSERT INTO gebruikers
            (
                lid_id,
                email,
                wachtwoord_hash,
                rol,
                goedkeuringsstatus,
                goedgekeurd_op,
                actief,
                mail_blacklist
            )
            VALUES
            (
                :lid_id,
                :email,
                :wachtwoord_hash,
                :rol,
                :goedkeuringsstatus,
                :goedgekeurd_op,
                :actief,
                :mail_blacklist
            )
            '
        );

        $statement->execute([
            'lid_id' => (int) $data['lid_id'],
            'email' => strtolower(trim((string) $data['email'])),
            'wachtwoord_hash' => password_hash(
                (string) $data['password'],
                PASSWORD_DEFAULT
            ),
            'rol' => (string) $data['rol'],
            'goedkeuringsstatus' => (string) (
                $data['goedkeuringsstatus']
                ?? User::APPROVAL_APPROVED
            ),
            'goedgekeurd_op' => $data['goedgekeurd_op'] ?? null,
            'actief' => !empty($data['actief']) ? 1 : 0,
            'mail_blacklist' => !empty($data['mail_blacklist']) ? 1 : 0,
        ]);

        return $this->database->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateAccess(
        int $id,
        array $data
    ): void {
        $statement = $this->database->prepare(
            '
            UPDATE gebruikers
            SET
                rol = :rol,
                actief = :actief,
                mail_blacklist = :mail_blacklist
            WHERE gebruiker_id = :id
            '
        );

        $statement->execute([
            'id' => $id,
            'rol' => (string) $data['rol'],
            'actief' => !empty($data['actief']) ? 1 : 0,
            'mail_blacklist' => !empty($data['mail_blacklist']) ? 1 : 0,
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function approve(
        int $id,
        array $data
    ): void {
        $statement = $this->database->prepare(
            '
            UPDATE gebruikers
            SET
                rol = :rol,
                goedkeuringsstatus = :approval_status,
                goedgekeurd_op = NOW(),
                actief = 1,
                mail_blacklist = :mail_blacklist
            WHERE gebruiker_id = :id
            '
        );

        $statement->execute([
            'id' => $id,
            'rol' => (string) $data['rol'],
            'approval_status' => User::APPROVAL_APPROVED,
            'mail_blacklist' => !empty($data['mail_blacklist']) ? 1 : 0,
        ]);
    }

    public function updateEmailAndActiveByMemberId(
        int $memberId,
        string $email,
        bool $active
    ): void {
        $statement = $this->database->prepare(
            '
            UPDATE gebruikers
            SET
                email = :email,
                actief = :actief
            WHERE lid_id = :member_id
            '
        );

        $statement->execute([
            'member_id' => $memberId,
            'email' => strtolower(trim($email)),
            'actief' => $active ? 1 : 0,
        ]);
    }

    public function issuePasswordResetToken(
        int $userId,
        string $tokenHash
    ): bool {
        $statement = $this->database->prepare(
            '
            UPDATE gebruikers
            SET
                reset_token = :token_hash,
                reset_token_expires = DATE_ADD(NOW(), INTERVAL 60 MINUTE)
            WHERE gebruiker_id = :user_id
              AND actief = 1
              AND goedkeuringsstatus = :approval_status
              AND (
                  reset_token IS NULL
                  OR reset_token_expires IS NULL
                  OR reset_token_expires <= DATE_ADD(NOW(), INTERVAL 55 MINUTE)
              )
            '
        );
        $statement->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);

        return $statement->rowCount() === 1;
    }

    public function clearPasswordResetToken(
        int $userId,
        string $tokenHash
    ): void {
        $statement = $this->database->prepare(
            '
            UPDATE gebruikers
            SET
                reset_token = NULL,
                reset_token_expires = NULL
            WHERE gebruiker_id = :user_id
              AND reset_token = :token_hash
            '
        );
        $statement->execute([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
        ]);
    }

    public function hasValidPasswordResetToken(string $tokenHash): bool
    {
        $statement = $this->database->prepare(
            '
            SELECT 1
            FROM gebruikers
            WHERE reset_token = :token_hash
              AND reset_token_expires > NOW()
              AND actief = 1
              AND goedkeuringsstatus = :approval_status
            LIMIT 1
            '
        );
        $statement->execute([
            'token_hash' => $tokenHash,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);

        return $statement->fetchColumn() !== false;
    }

    public function resetPasswordWithToken(
        string $tokenHash,
        string $passwordHash
    ): bool {
        $statement = $this->database->prepare(
            '
            UPDATE gebruikers
            SET
                wachtwoord_hash = :password_hash,
                wachtwoord_moet_wijzigen = 0,
                reset_token = NULL,
                reset_token_expires = NULL
            WHERE reset_token = :token_hash
              AND reset_token_expires > NOW()
              AND actief = 1
              AND goedkeuringsstatus = :approval_status
            '
        );
        $statement->execute([
            'token_hash' => $tokenHash,
            'password_hash' => $passwordHash,
            'approval_status' => User::APPROVAL_APPROVED,
        ]);

        return $statement->rowCount() === 1;
    }

    protected function map(array $row): User
    {
        return new User(
            gebruikerId: (int) ($row['gebruiker_id'] ?? 0),
            lidId: (int) ($row['lid_id'] ?? 0),
            email: (string) ($row['email'] ?? ''),
            rol: (string) ($row['rol'] ?? User::ROLE_MEMBER),
            actief: (bool) ($row['actief'] ?? false),
            mailBlacklist: (bool) ($row['mail_blacklist'] ?? false),
            passwordHash: (string) ($row['wachtwoord_hash'] ?? ''),
            resetToken: $this->nullableString($row['reset_token'] ?? null),
            resetTokenExpires: $this->nullableString(
                $row['reset_token_expires'] ?? null
            ),
            voornaam: (string) ($row['voornaam'] ?? ''),
            achternaam: (string) ($row['achternaam'] ?? ''),
            goedkeuringsstatus: (string) (
                $row['goedkeuringsstatus']
                ?? User::APPROVAL_APPROVED
            ),
            goedgekeurdOp: $this->nullableString(
                $row['goedgekeurd_op'] ?? null
            )
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === ''
            ? null
            : $value;
    }
}
