<?php

declare(strict_types=1);

namespace AEFS\Repositories;

use AEFS\Core\Container;
use AEFS\Core\Database;
use PDO;

abstract class BaseRepository
{
    protected Database $database;
    protected PDO $pdo;

    public function __construct()
    {
        $this->database = Container::get(Database::class);
        $this->pdo = $this->database->pdo();
    }
}