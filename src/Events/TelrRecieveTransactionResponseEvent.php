<?php

namespace SudhanshuMittal\TelrGateway\Events;

use SudhanshuMittal\TelrGateway\TelrTransaction;

class TelrRecieveTransactionResponseEvent
{
    /**
     * @var TelrTransaction
     */
    public $transaction;

    /**
     * @var \stdClass
     */
    public $response;

    /**
     * SuccessTransactionEvent constructor.
     *
     * @param TelrTransaction $transaction
     * @param \stdClass $response
     */
    public function __construct(TelrTransaction $transaction, \stdClass $response)
    {
        $this->transaction = $transaction;
        $this->response = $response;
    }
}
