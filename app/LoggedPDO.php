<?php
namespace App;

use PDO;
use PDOException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LoggedPDO extends PDO
{
    private $logger;

    // Add an extra parameter ($logger) to accept a Monolog instance
    public function __construct($dsn, $username = null, $password = null, $options = [], Logger $logger = null)
    {
        parent::__construct($dsn, $username, $password, $options);

        // Store the logger
        $this->logger = $logger;
    }

    public function query($statement, $mode = PDO::ATTR_DEFAULT_FETCH_MODE, ...$fetch_mode_args)
    {
        if ($this->logger) {
            $this->logger->debug("Executing query: " . $statement);
        }
        return parent::query($statement, $mode, ...$fetch_mode_args);
    }

    public function exec($statement)
    {
        if ($this->logger) {
            $this->logger->debug("Executing exec: " . $statement);
        }
        return parent::exec($statement);
    }

    public function prepare($statement, $options = [])
    {
        if ($this->logger) {
            $this->logger->debug("Preparing query: " . $statement);
        }
        return parent::prepare($statement, $options);
    }
}
