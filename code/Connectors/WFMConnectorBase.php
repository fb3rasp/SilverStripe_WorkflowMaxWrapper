<?php

class WFMConnectorBase {

    static public $api_key = "";

    static public $account_key = "";

    public function getAuthString() {
        $auth = '?apiKey='.WFMConnectorBase::$api_key.'&accountKey='.WFMConnectorBase::$account_key;
        return $auth;
    }

    public function getRESTService() {
        $service = new RestfulService("http://api.workflowmax.com/",0);
        return $service;        
    }

    public function getSecureRESTService() {
        $service = new RestfulService("https://api.workflowmax.com/",0);
        return $service;        
    }

    /**
     * This method validates if the response of WorkflowMax may contain an error message.
     *
     * An error message, comming from WorkflowMax will be converted into an Excpetion. If no
     * error is found, the methid will return. Exceptions are only throws in case of an error.
     *
     * @param SS_HTTPResponse $response the response of the RestfulService->request call, which gets validated.
     *
     * @throws Exception
     *
     * @return void
     */
    protected function validateResponse(SS_HTTPResponse $response) {
        if ($response->getStatusCode() != 200) {
            throw new Exception('Server responded with non 200 statuscode.');
        }

        $status = $response->xpath("/Response/Status");
        if (!$status) {
            throw new Exception('Server responded with an invalide error message.');
        }

        $status = (string)$status[0];

        if ($status == 'ERROR') {
            $message = $response->xpath("/Response/ErrorDescription");
            $message = (string)$message[0];
            throw new Exception($message);
        }        
    }

}