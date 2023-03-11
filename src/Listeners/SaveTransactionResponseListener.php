<?php

namespace SudhanshuMittal\TelrGateway\Listeners;

use SudhanshuMittal\TelrGateway\Events\TelrSuccessTransactionEvent;
use SudhanshuMittal\TelrGateway\Events\TelrFailedTransactionEvent;

class SaveTransactionResponseListener
{
    /**
     * @param TelrSuccessTransactionEvent|TelrFailedTransactionEvent $event
     */
    public function handle($event)
    {
        $event->transaction->fill(['response' => $event->response, 'status' => 1])->save();
    }
}
