<?php

namespace App\Services\GeoFlow\AiVisibility;

use App\Models\AiVisibilityRun;
use RuntimeException;
use Throwable;

final class AiVisibilityRunException extends RuntimeException
{
    /**
     * @param  array<string, AiVisibilityRun>  $completedRuns
     */
    public function __construct(
        public readonly AiVisibilityRun $run,
        Throwable $previous,
        public readonly array $completedRuns = [],
    ) {
        parent::__construct($previous->getMessage(), 0, $previous);
    }
}
