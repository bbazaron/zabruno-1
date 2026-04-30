<?php

namespace App\Console\Commands;

use App\Jobs\ReconcilePendingYooKassaPayments;
use Illuminate\Console\Command;

class ReconcileYooKassaPaymentsCommand extends Command
{
    protected $signature = 'yookassa:reconcile {--hours=24 : Check orders created within last N hours}';

    protected $description = 'Reconcile pending payment orders with YooKassa status.';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $job = new ReconcilePendingYooKassaPayments($hours);
        app()->call([$job, 'handle']);

        $this->info("YooKassa reconciliation finished (hours={$hours}).");

        return self::SUCCESS;
    }
}
