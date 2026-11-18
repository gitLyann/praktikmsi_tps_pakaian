<?php

namespace App\Observers;

use App\Models\RestockRequest;
use App\Services\EisActivityLogger;

class EisRestockObserver
{
    public function __construct(private readonly EisActivityLogger $logger) {}

    public function created(RestockRequest $request): void
    {
        $this->logger->logRestockRequested($request);
    }

    public function updated(RestockRequest $request): void
    {
        if ($request->wasChanged('status')) {
            $this->logger->logRestockDecided($request);
        }
    }
}
