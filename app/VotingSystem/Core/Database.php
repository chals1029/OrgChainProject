<?php

namespace App\VotingSystem\Core;

use Illuminate\Support\Facades\DB;
use PDO;

class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (! self::$connection instanceof PDO) {
            self::$connection = DB::connection('voting')->getPdo();
            self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }

        return self::$connection;
    }
}
