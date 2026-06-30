<?php

declare(strict_types=1);

namespace AEFS\Controllers;

use AEFS\Core\Config;
use AEFS\Core\Container;
use AEFS\Core\Database;
use AEFS\Core\Logger;
use AEFS\Core\Request;
use AEFS\Core\View;

abstract class BaseController
{
    protected Database $db;
    protected Config $config;
    protected Logger $logger;
    protected Request $request;

    public function __construct()
    {
        $this->db = Container::get(Database::class);
        $this->config = Container::get(Config::class);
        $this->logger = Container::get(Logger::class);
        $this->request = new Request();
    }

    protected function view(string $view, array $data = []): void
    {
        View::render($view, $data);
    }
}