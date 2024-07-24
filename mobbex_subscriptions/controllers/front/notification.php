<?php

defined('_PS_VERSION_') || exit;

class Mobbex_SubscriptionsNotificationModuleFrontController extends ModuleFrontController
{
    /** @var \Mobbex\Subscriptions\Helper */
    public $helper;

    /** @var \Mobbex\PS\Checkout\Models\OrderHelper */
    public $mbbxHelper;

    /** @var \Mobbex\PS\Checkout\Models\OrderUpdate */
    public $orderUpdate;

    public function postProcess()
    {
        $this->helper      = new \Mobbex\Subscriptions\Helper;
        $this->orderUpdate = new \Mobbex\PS\Checkout\Models\OrderUpdate;
        $this->mbbxHelper  = new \Mobbex\PS\Checkout\Models\OrderHelper;

        // We don't do anything if the module has been disabled by the merchant
        if ($this->module->active == false)
            $this->helper->log('fatal', 'Notification On Module Inactive (subscriptions endpoint)', $_REQUEST);

        // Get current action
        $action = Tools::getValue('action');

        if ($action == 'webhook')
            return $this->webhook();
    }

    /**
     * Handles the payment notification.
     */
    public function webhook()
    {
        $postData    = isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/json' ? json_decode(file_get_contents('php://input'), true) : $_POST;

        if (empty($postData['data']) || empty($postData['type']))
            $this->helper->log('fatal', 'notification > webhook | Invalid Webhook Data', $postData);

        // Get order and customer data
        $cartId      = Tools::getValue('id_cart');
        $cart        = new \Cart($cartId);
        $orderHelper = new \Mobbex\PS\Checkout\Models\OrderHelper;
        $customer    = $orderHelper->getCustomer($cart);

        // Manage data based on webhook type
        if ($postData['type'] == 'checkout'){
            // Get subscription and subscriber from uid
            $subscription = $this->helper->getSubscriptionByUid($postData['data']['subscriptions'][0]['subscription']);
            $subscriber   = $this->helper->getSubscriberByUid($postData['data']['subscriptions'][0]['subscriber']);

            // If the subscriber does not exist, create it and save it to the database.
            if(!$subscriber){
                $subscriber = new \MobbexSubscriber(
                    $cart->id,
                    $subscription->uid,
                    (bool) \Configuration::get('MOBBEX_TEST_MODE'),
                    $customer['name'],
                    $customer['email'],
                    $customer['phone'],
                    $customer['identification'],
                    $customer['uid']
                );
                $subscriber->save($postData['data']['subscriptions'][0]['subscriber']);
            }

            if (!$subscription || !$subscriber)
                $this->helper->log('error', 'Subscription or subscriber cannot be loaded', $postData);

        } elseif ($postData['type'] == 'subscription:execution') {
            $subscription = $this->helper->getSubscriptionByUid($postData['data']['subscription']['uid']);
            $subscriber   = $this->helper->getSubscriberByUid($postData['data']['subscriber']['uid']);
            $dates = $subscription->calculateDates();

            $execution = new MobbexExecution(
                $postData['data']['execution']['uid'],
                $subscription->uid,
                $subscriber->uid,
                $postData['data']['payment']['status']['code'],
                $postData['data']['payment']['total'],
                $dates['current'],
                json_encode($postData['data'])
            );
            $execution->save();

            // Update execution dates
            $subscriber->last_execution = $dates['current'];
            $subscriber->next_execution = $dates['next'];

            if (!$subscriber->start_date || strtotime($subscriber->start_date) < 0)
                $subscriber->start_date = $subscriber->last_execution;

            $subscriber->update();
        } elseif ($postData['type'] == "subscription:subscriber:deleted"){
            $this->helper->deleteSubscriber($postData['data']['subscriber']['uid']);
        }

        die('OK: ' . \Mobbex\PS\Checkout\Models\Config::MODULE_VERSION);
    }
}