<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Base model providing shared PDO access to all child models.
 */
abstract class BaseModel
{
    protected Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }
}
