<?php

// 1) Include Composer autoload (loads Monolog and any other dependencies)
require_once __DIR__ . '/../vendor/autoload.php';

// 2) Include the LoggedPDO class (adjust the path as needed)
require_once __DIR__ . '/../app/LoggedPDO.php';

use App\LoggedPDO;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

// 3) Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// 4) Get DB credentials from .env
$db_host = $_ENV['DB_HOST'];
$db_name = $_ENV['DB_NAME'];
$db_user = $_ENV['DB_USER'];
$db_pass = $_ENV['DB_PASS'];

// 5) Construct the DSN
$dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";

try {
    // 6) Create a Monolog logger
    $logger = new Logger('sql_logger');
    // Logs will be written to logs/sql.log (make sure the directory is writable)
    $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/sql.log', Logger::DEBUG));

    // 7) Create a LoggedPDO instance, passing in the logger
    $conn = new LoggedPDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ], $logger);

} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// (Optional) Keep or define your custom function here
if (!function_exists('unique_id')) {
    function unique_id()
    {
        $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $rand = [];
        $length = strlen($str) - 1;
        for ($i = 0; $i < 20; $i++) {
            $n = mt_rand(0, $length);
            $rand[] = $str[$n];
        }
        return implode($rand);
    }
}
