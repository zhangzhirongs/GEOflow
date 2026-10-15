<?php

namespace App\Services\Admin\Analytics;

use App\Models\AiVisibilityRun;
use RuntimeException;
use Throwable;

class GrowthAiCollectionException extends RuntimeException
{
    /**
     * @param  array<string, AiVisibilityRun>  $runs
     */
    public function __construct(
        string $message,
        public readonly array $runs,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
