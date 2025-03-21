<?php
namespace App;

use PDO;
use PDOStatement;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LoggedPDO extends PDO
{
    private ?Logger $logger;

    // Accept a Monolog instance (explicitly nullable)
    public function __construct($dsn, $username = null, $password = null, $options = [], ?Logger $logger = null)
    {
        parent::__construct($dsn, $username, $password, $options);
        $this->logger = $logger;
    }

    // Explicitly declare return type as PDOStatement|false
    public function query($statement, $mode = PDO::ATTR_DEFAULT_FETCH_MODE, ...$fetch_mode_args): PDOStatement|false
    {
        if ($this->logger) {
            $this->logger->debug("Executing query: " . $statement);
        }
        return parent::query($statement, $mode, ...$fetch_mode_args);
    }

    // Explicitly declare return type as int|false
    public function exec($statement): int|false
    {
        if ($this->logger) {
            $this->logger->debug("Executing exec: " . $statement);
        }
        return parent::exec($statement);
    }

    // Explicitly declare return type as PDOStatement|false
    public function prepare($statement, $options = []): PDOStatement|false
    {
        if ($this->logger) {
            $this->logger->debug("Preparing query: " . $statement);
        }
        return parent::prepare($statement, $options);
    }
}
