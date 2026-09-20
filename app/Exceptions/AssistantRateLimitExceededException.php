<?php

namespace App\Exceptions;

use RuntimeException;

class AssistantRateLimitExceededException extends RuntimeException
{
    public function __construct(public readonly int $retryAfter)
    {
        parent::__construct(
            'The assistant request limit has been reached. Please try again later.'
        );
    }
}
