<?php

namespace App\Listeners;

use App\Events\DealClosed;
use App\Models\Deal;
use App\Services\AutomationLogger;
use App\Services\NotificationService;
use App\Services\ProjectComposer;
use App\Services\WorkloadService;

/**
 * Trigger "Deal = Closing": create Project + Invoice Request, notify owner
 * (blueprint automation). Runs inside the transaction of the caller.
 */
class HandleDealClosing
{
    public function handle(DealClosed $event): void
    {
        $deal = $event->deal;

        try {
            ProjectComposer::createFromDeal($deal, $deal->pic_employee_id);
            AutomationLogger::log('deal_closing', 'deal', $deal->deal_id, 'success');

            NotificationService::send(
                $deal->pic_employee_id,
                'Deal ditutup',
                "Deal {$deal->deal_id} telah Closing. Project dan Invoice Request otomatis dibuat.",
                route('projects.index'),
                'success',
            );
            WorkloadService::syncFor($deal->pic_employee_id);
        } catch (\Throwable $e) {
            AutomationLogger::log('deal_closing', 'deal', $deal->deal_id, 'failed', $e->getMessage());
            report($e);
            throw $e;
        }
    }
}