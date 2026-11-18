<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Services\EisActivityLogger;

class EisActivityObserver
{
    public function __construct(private readonly EisActivityLogger $logger) {}

    public function created(Transaction $transaction): void
    {
        $this->logger->logTransaction($transaction);
    }

    public function updated(Transaction $transaction): void
    {
        if ($transaction->wasChanged(['status', 'employee_id'])) {
            $this->logger->logTransaction($transaction);
        }
    }
}
