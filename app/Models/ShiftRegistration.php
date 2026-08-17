<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeImmutable;

final class ShiftRegistration
{
    public const STATUS_WACHTEND = 'wachtend';
    public const STATUS_BEVESTIGD = 'bevestigd';
    public const STATUS_RESERVE = 'reserve';
    public const STATUS_GEWEIGERD = 'geweigerd';
    public const STATUS_GEANNULEERD = 'geannuleerd';

    public function __construct(
        public readonly int $inschrijvingId,
        public readonly int $shiftId,
        public readonly int $lidId,
        public readonly string $status,
        public readonly ?string $opmerkingLid,
        public readonly ?int $goedgekeurdDoor,
        public readonly ?string $goedgekeurdOp,
        public readonly ?int $geannuleerdDoor,
        public readonly ?string $geannuleerdOp,
        public readonly ?string $annulatieReden,
        public readonly bool $aanwezig,
        public readonly ?string $aanwezigAfgevinktOp,
        public readonly string $aangemaaktOp,
        public readonly ?string $bijgewerktOp,
        public readonly ?string $lidVoornaam = null,
        public readonly ?string $lidAchternaam = null,
        public readonly ?string $lidEmail = null,
        public readonly ?string $shiftNaam = null,
        public readonly ?string $shiftStartOp = null,
        public readonly ?string $shiftEindOp = null,
        public readonly ?string $eventTitel = null,
        public readonly ?string $typeNaam = null,
        public readonly ?string $goedgekeurdDoorNaam = null,
        public readonly ?string $geannuleerdDoorNaam = null
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_WACHTEND => 'Wachtend',
            self::STATUS_BEVESTIGD => 'Bevestigd',
            self::STATUS_RESERVE => 'Reserve',
            self::STATUS_GEWEIGERD => 'Geweigerd',
            self::STATUS_GEANNULEERD => 'Geannuleerd',
        ];
    }

    public function isActief(): bool
    {
        return in_array(
            $this->status,
            [
                self::STATUS_WACHTEND,
                self::STATUS_BEVESTIGD,
                self::STATUS_RESERVE,
            ],
            true
        );
    }

    public function isWachtend(): bool
    {
        return $this->status === self::STATUS_WACHTEND;
    }

    public function isBevestigd(): bool
    {
        return $this->status === self::STATUS_BEVESTIGD;
    }

    public function isReserve(): bool
    {
        return $this->status === self::STATUS_RESERVE;
    }

    public function isGeweigerd(): bool
    {
        return $this->status === self::STATUS_GEWEIGERD;
    }

    public function isGeannuleerd(): bool
    {
        return $this->status === self::STATUS_GEANNULEERD;
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status]
            ?? ucfirst($this->status);
    }

    public function statusCssClass(): string
    {
        return match ($this->status) {
            self::STATUS_BEVESTIGD => 'badge-success',
            self::STATUS_RESERVE => 'badge-info',
            self::STATUS_GEWEIGERD,
            self::STATUS_GEANNULEERD => 'badge-danger',
            default => 'badge-warning',
        };
    }

    public function lidNaam(): string
    {
        $naam = trim(
            sprintf(
                '%s %s',
                $this->lidVoornaam ?? '',
                $this->lidAchternaam ?? ''
            )
        );

        return $naam !== ''
            ? $naam
            : 'Onbekend lid';
    }

    public function displayAangemaaktOp(): string
    {
        return (new DateTimeImmutable($this->aangemaaktOp))
            ->format('d/m/Y H:i');
    }

    public function displayShiftPeriode(): string
    {
        if ($this->shiftStartOp === null) {
            return '-';
        }

        $start = new DateTimeImmutable($this->shiftStartOp);

        if ($this->shiftEindOp === null) {
            return $start->format('d/m/Y H:i');
        }

        $end = new DateTimeImmutable($this->shiftEindOp);
        $endLabel = $end->format('H:i');

        if (
            $start->format('Y-m-d')
            !== $end->format('Y-m-d')
        ) {
            $endLabel .= ' (+1 dag)';
        }

        return sprintf(
            '%s, %s – %s',
            $start->format('d/m/Y'),
            $start->format('H:i'),
            $endLabel
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toAuditArray(): array
    {
        return [
            'shift_id' => $this->shiftId,
            'lid_id' => $this->lidId,
            'status' => $this->status,
            'opmerking_lid' => $this->opmerkingLid,
            'goedgekeurd_door' => $this->goedgekeurdDoor,
            'goedgekeurd_op' => $this->goedgekeurdOp,
            'geannuleerd_door' => $this->geannuleerdDoor,
            'geannuleerd_op' => $this->geannuleerdOp,
            'annulatie_reden' => $this->annulatieReden,
            'aanwezig' => $this->aanwezig,
            'aanwezig_afgevinkt_op' => $this->aanwezigAfgevinktOp,
        ];
    }
}