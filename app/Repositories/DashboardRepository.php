<?php

declare(strict_types=1);

namespace App\Repositories;

use AEFS\Database\DB;
use AEFS\Database\Query\Expression;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Shift;
use App\Models\User;

final class DashboardRepository
{
    public function countActiveMembers(): int
    {
        return DB::table('leden as l')
            ->join(
                'gebruikers as g',
                'g.lid_id',
                '=',
                'l.lid_id'
            )
            ->where('l.actief', '=', 1)
            ->where('g.actief', '=', 1)
            ->where(
                'g.goedkeuringsstatus',
                '=',
                User::APPROVAL_APPROVED
            )
            ->count('l.lid_id');
    }

    public function countPendingRegistrations(): int
    {
        return DB::table('gebruikers')
            ->where(
                'goedkeuringsstatus',
                '=',
                User::APPROVAL_PENDING
            )
            ->count();
    }

    public function countPendingEventCancellations(): int
    {
        return DB::table('event_inschrijvingen')
            ->whereNotNull('annulatie_aangevraagd_op')
            ->whereNull('uitgeschreven_op')
            ->count();
    }

    public function countPendingEventRegistrations(): int
    {
        return DB::table('event_inschrijvingen as ei')
            ->join(
                'evenementen as e',
                'e.event_id',
                '=',
                'ei.event_id'
            )
            ->where(
                'ei.status',
                '=',
                EventRegistration::STATUS_WACHTEND
            )
            ->whereNull('ei.uitgeschreven_op')
            ->whereNull('ei.annulatie_aangevraagd_op')
            ->where(
                Expression::raw(
                    'COALESCE(`e`.`einddatum`, `e`.`startdatum`)'
                ),
                '>=',
                date('Y-m-d')
            )
            ->count();
    }

    public function countActiveUsers(): int
    {
        return DB::table('gebruikers')
            ->where('actief', '=', 1)
            ->where(
                'goedkeuringsstatus',
                '=',
                User::APPROVAL_APPROVED
            )
            ->count();
    }

    public function countUpcomingEvents(
        bool $visibleToMembersOnly = false
    ): int {
        $query = DB::table('evenementen')
            ->where(
                Expression::raw(
                    'COALESCE(`einddatum`, `startdatum`)'
                ),
                '>=',
                date('Y-m-d')
            );

        if ($visibleToMembersOnly) {
            $query->where(
                'status',
                '<>',
                Event::STATUS_CONCEPT
            );
        }

        return $query->count();
    }

    public function countOpenShifts(): int
    {
        return DB::table('shifts')
            ->where(
                'status',
                '=',
                Shift::STATUS_ACTIEF
            )
            ->where(
                'eind_op',
                '>=',
                date('Y-m-d H:i:s')
            )
            ->count();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function latestApprovedMembers(int $limit = 5): array
    {
        return DB::table('leden as l')
            ->select(
                'l.lid_id',
                'l.voornaam',
                'l.achternaam',
                'l.gemeente'
            )
            ->join(
                'gebruikers as g',
                'g.lid_id',
                '=',
                'l.lid_id'
            )
            ->where('l.actief', '=', 1)
            ->where('g.actief', '=', 1)
            ->where(
                'g.goedkeuringsstatus',
                '=',
                User::APPROVAL_APPROVED
            )
            ->orderBy('l.lid_id', 'DESC')
            ->limit(max(1, $limit))
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pendingRegistrations(int $limit = 5): array
    {
        return DB::table('gebruikers as g')
            ->select(
                'g.gebruiker_id',
                'g.email',
                'g.rol',
                'g.goedkeuringsstatus',
                'l.voornaam',
                'l.achternaam'
            )
            ->join(
                'leden as l',
                'l.lid_id',
                '=',
                'g.lid_id'
            )
            ->where(
                'g.goedkeuringsstatus',
                '=',
                User::APPROVAL_PENDING
            )
            ->orderBy('g.gebruiker_id', 'DESC')
            ->limit(max(1, $limit))
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pendingEventCancellations(int $limit = 5): array
    {
        return DB::table('event_inschrijvingen as ei')
            ->select(
                'ei.inschrijving_id',
                'ei.event_id',
                'ei.annulatie_aangevraagd_op',
                'ei.uitschrijfreden',
                'e.titel as event_titel',
                'l.voornaam',
                'l.achternaam'
            )
            ->join(
                'evenementen as e',
                'e.event_id',
                '=',
                'ei.event_id'
            )
            ->join(
                'leden as l',
                'l.lid_id',
                '=',
                'ei.lid_id'
            )
            ->whereNotNull('ei.annulatie_aangevraagd_op')
            ->whereNull('ei.uitgeschreven_op')
            ->orderBy('ei.annulatie_aangevraagd_op', 'ASC')
            ->limit(max(1, $limit))
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function pendingEventRegistrations(int $limit = 5): array
    {
        return DB::table('event_inschrijvingen as ei')
            ->select(
                'ei.inschrijving_id',
                'ei.event_id',
                'ei.aangemeld_op',
                'e.titel as event_titel',
                'l.voornaam',
                'l.achternaam'
            )
            ->join(
                'evenementen as e',
                'e.event_id',
                '=',
                'ei.event_id'
            )
            ->join(
                'leden as l',
                'l.lid_id',
                '=',
                'ei.lid_id'
            )
            ->where(
                'ei.status',
                '=',
                EventRegistration::STATUS_WACHTEND
            )
            ->whereNull('ei.uitgeschreven_op')
            ->whereNull('ei.annulatie_aangevraagd_op')
            ->where(
                Expression::raw(
                    'COALESCE(`e`.`einddatum`, `e`.`startdatum`)'
                ),
                '>=',
                date('Y-m-d')
            )
            ->orderBy('ei.aangemeld_op', 'DESC')
            ->limit(max(1, $limit))
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function upcomingEvents(
        int $limit = 5,
        bool $visibleToMembersOnly = false
    ): array {
        $query = DB::table('evenementen')
            ->where(
                Expression::raw(
                    'COALESCE(`einddatum`, `startdatum`)'
                ),
                '>=',
                date('Y-m-d')
            );

        if ($visibleToMembersOnly) {
            $query->where(
                'status',
                '<>',
                Event::STATUS_CONCEPT
            );
        }

        return $query
            ->orderBy('startdatum', 'ASC')
            ->limit(max(1, $limit))
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function openShifts(int $limit = 5): array
    {
        return DB::table('shifts')
            ->where(
                'status',
                '=',
                Shift::STATUS_ACTIEF
            )
            ->where(
                'eind_op',
                '>=',
                date('Y-m-d H:i:s')
            )
            ->orderBy('start_op', 'ASC')
            ->limit(max(1, $limit))
            ->get();
    }
}
