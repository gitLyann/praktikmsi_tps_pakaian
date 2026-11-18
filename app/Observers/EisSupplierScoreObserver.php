<?php

namespace App\Observers;

use App\Models\DssSupplierScore;
use App\Services\EisActivityLogger;

class EisSupplierScoreObserver
{
    public function __construct(private readonly EisActivityLogger $logger) {}

    public function created(DssSupplierScore $score): void
    {
        $this->logger->logSupplierEvaluated($score);
    }
}
