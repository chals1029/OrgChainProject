<?php

namespace App\VotingSystem\Core;

use PDO;

abstract class Model
{
    protected function db(): PDO
    {
        return Database::connection();
    }
}
