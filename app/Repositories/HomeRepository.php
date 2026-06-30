<?php

declare(strict_types=1);

namespace AEFS\Repositories;

final class HomeRepository extends BaseRepository
{
    public function databaseVersion(): string
    {
        return (string)$this->pdo
            ->query("SELECT VERSION()")
            ->fetchColumn();
    }
}