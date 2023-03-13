<?php

namespace SudhanshuMittal\TelrGateway;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\Request;
use SudhanshuMittal\TelrGateway\Events\TelrCreateRequestEvent;
use SudhanshuMittal\TelrGateway\Events\TelrFailedTransactionEvent;
use SudhanshuMittal\TelrGateway\Events\TelrRecieveTransactionResponseEvent;
use SudhanshuMittal\TelrGateway\Events\TelrSuccessTransactionEvent;
use Str;

class TelrManager
{
    /**
     * Prepare create request
     *
     * @param $orderId
     * @param $amount
     * @param $description
     * @param array $billingParams
     * @param array $req_params
     * @return \SudhanshuMittal\TelrGateway\CreateTelrRequest
     */
    public function prepareCreateRequest($orderId, $amount, $description, array $billingParams = [], array $req_params = [])
    {
        $createTelrRequest = (new CreateTelrRequest($orderId, $amount))->setDesc($description);

        //Set Telr request lang
        $createTelrRequest->setLangCode(app()->getLocale());

        // Associate billing params to fields
        foreach ($billingParams as $key => $value) {
            $methodName = ('setBilling' . Str::studly($key));
            if (method_exists($createTelrRequest, $methodName)) {
                $createTelrRequest->$methodName($value);
            }
        }

        if ($req_params) {
            $createTelrRequest->setExtraReq($req_params);
        }

        return $createTelrRequest;
    }

    /**
     * Initiate create request on telr
     *
     * @param $orderId
     * @param $amount
     * @param $description
     * @param array $billingParams
     * @param array $req_params
     * @return \SudhanshuMittal\TelrGateway\TelrURL
     * @throws \Exception
     */
    public function pay($orderId, $amount, $description, array $billingParams = [], array $req_params = [])
    {
        $createRequest = $this->prepareCreateRequest($orderId, $amount, $description, $billingParams, $req_params);
        $result = $this->callTelrServer($createRequest->getEndPointURL(), $createRequest->toArray());

        // Validate if response has error messages
        if (isset($result->error)) {
            throw new \Exception($result->error->message . '. Note: ' . $result->error->message);
        }
        // Dispatch event
        event(new TelrCreateRequestEvent($createRequest, $result));

        return new TelrURL($result->order->url);
    }

    /**
     * Fetch the transaction result
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Database\Eloquent\ModelNotFoundException|TelrTransaction
     * @throws \Exception
     */
    public function handleTransactionResponse(Request $request)
    {
        $transaction = TelrTransaction::findOrFail($request->cart_id);
        $trxResultRequest = new TelrTransactionResultRequest($transaction);

        $result = $this->callTelrServer($trxResultRequest->getEndPointURL(), $trxResultRequest->toArray());

        // Validate if response has error messages
        if (isset($result->error)) {
            throw new \Exception($result->error->message . '. Note: ' . $result->error->note);
        }

        // Dispatch event for after receiving telr response
        event(new TelrRecieveTransactionResponseEvent($transaction, $result));

        // Is success transaction
        if (3 === $result->order->status->code && 'paid' === strtolower($result->order->status->text)) {
            // Mark the transaction as approved
            $transaction->approve();

            // Dispatch success transaction
            event(new TelrSuccessTransactionEvent($transaction, $result));

            return $transaction;
        }

        // Mark the transaction as failed
        $transaction->failed();

        // Dispatch failed transaction
        event(new TelrFailedTransactionEvent($transaction, $result));

        return $transaction;
    }

    /**
     * Call the telr server
     *
     * @param $endPoint
     * @param $formParams
     * @return mixed
     */
    protected function callTelrServer($endPoint, $formParams)
    {
        $client = new Client();
        $result = $client->post($endPoint, ['form_params' => $formParams]);

        // Validate if response is equal 200
        if (200 != $result->getStatusCode()) {
            throw new ClientException('The response is ' . $result->getStatusCode());
        }

        // Convert json response into object
        return \GuzzleHttp\json_decode($result->getBody()->getContents());
    }
}
