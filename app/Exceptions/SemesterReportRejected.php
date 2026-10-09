<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A single semester report submission was refused under its row locks.
 */
class SemesterReportRejected extends RuntimeException
{
    public const LOCKED = 'locked';

    public const MISSING = 'missing';

    public const GUARD = 'guard';

    /**
     * @param  list<string>  $types  report types without an actual stored file
     */
    public function __construct(
        public readonly string $reason,
        string $message = '',
        public readonly array $types = [],
    ) {
        parent::__construct($message);
    }
}
