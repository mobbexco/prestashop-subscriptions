<?php

class Mobbex_SubscriptionsExecutionModuleFrontController extends \ModuleFrontController
{
    public function __construct()
    {
        parent::__construct();
        $this->api    = new \Mobbex\Subscriptions\Api;
        $this->config = new \Mobbex\PS\Checkout\Models\Config();
        $this->logger = new \Mobbex\PS\Checkout\Models\Logger();
    }

    public function postProcess()
    {
        // We don't do anything if the module has been disabled by the merchant
        if ($this->module->active == false)
            $this->logger->log('fatal', 'Execution On Module Inactive (subscriptions endpoint)', $_REQUEST);

        if (Tools::getValue('hash') !== md5($this->config->settings['api_key'] . '!' . $this->config->settings['access_token']))
            $this->logger->log('fatal', 'Invalid hash in execution controller');

        $subscriber   = Tools::getValue('subscriber');
        $subscription = Tools::getValue('subscription');
        $execution    = Tools::getValue('execution');
        $url          = Tools::getValue('url');
        
        $data = [
            'uri'    => "subscriptions/$subscription/subscriber/$subscriber/execution/$execution/action/retry",
            'method' => 'GET',
        ];

        try {
            //Retry execution
            \Mobbex\Subscriptions\Api::request($data);
            //Get execution data
            $data  = \MobbexExecution::getData($execution);
            $data['context']['status'] = "retried successfully";
            $data  = json_encode($data);
            //Update execution data in db
            $query = "UPDATE `" . _DB_PREFIX_ . "mobbex_execution` SET data='$data' WHERE uid='$execution'";
            Db::getInstance()->Execute($query);

        } catch (\Mobbex\Subscriptions\Exception $e) {
            $this->logger->log('fatal', 'execution > postProcess | Failed to retry subscription execute: '. $e->getMessage(), ['subscriber_uid' => $subscriber, 'subscription_uid' => $subscription, 'execution_uid' => $execution]);
        }
        
        Tools::redirectAdmin($url);
    }
}