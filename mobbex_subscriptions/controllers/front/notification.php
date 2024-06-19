<?php

defined('_PS_VERSION_') || exit;

class Mobbex_SubscriptionsNotificationModuleFrontController extends ModuleFrontController
{
    /** @var \Mobbex\Subscriptions\Helper */
    public $helper;

    /** @var \Mobbex\PS\Checkout\Models\OrderUpdate */
    public $orderUpdate;

    /** @var \Mobbex\PS\Checkout\Models\OrderHelper */
    public $orderHelper;

    /** @var \Mobbex\PS\Checkout\Models\Transaction */
    public $transaction;

    public function postProcess()
    {
        $this->helper      = new \Mobbex\Subscriptions\Helper;
        $this->orderUpdate = new \Mobbex\PS\Checkout\Models\OrderUpdate;
        $this->orderHelper = new \Mobbex\PS\Checkout\Models\OrderHelper;
        $this->transaction = new \Mobbex\PS\Checkout\Models\Transaction;

        // We don't do anything if the module has been disabled by the merchant
        if ($this->module->active == false)
            $this->helper->log('fatal', 'Notification On Module Inactive (subscriptions endpoint)', $_REQUEST);

        // Get current action
        $action = Tools::getValue('action');

        if ($action == 'callback') {
            return $this->callback();
        } else if ($action == 'webhook') {
            return $this->webhook();
        }
    }

    /**
     * Handles the redirect after payment.
     */
    public function callback()
    {        
        $cart_id  = Context::getContext()->cookie->subscriber_cart_id;
        $customer = Context::getContext()->customer;
        $order_id = $this->orderHelper->getOrderByCartId($cart_id);
        $status   = Tools::getValue('status');
        
        // If order was not created
        if (empty($order_id)) {
            $seconds = 10;

            // Wait for webhook
            while ($seconds > 0 && !$order_id) {
                sleep(1);
                $seconds--;
                $order_id = $this->orderHelper->getOrderByCartId($cart_id);
            }
        }

        // Clear cart id cookie
        Context::getContext()->cookie->subscriber_cart_id = null;

        // If status is ok
        if ($status > 1 && $status < 400) {
            // Redirect to order confirmation
            Tools::redirect('index.php?controller=order-confirmation&' . http_build_query([
                'id_cart'       => $cart_id,
                'id_order'      => $order_id,
                'id_module'     => Module::getModuleIdByName('mobbex'),
                'key'           => $customer->secure_key,
            ]));
        } else {
            // Go back to checkout
            Tools::redirect('index.php?controller=order&step=1');
        }
    }

    /**
     * Handles the payment notification.
     */
    public function webhook()
    {
        $transaction = new \Mobbex\PS\Checkout\Models\Transaction;
        $postData    = isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/json' ? json_decode(file_get_contents('php://input'), true) : $_POST;

        if (empty($postData['data']) || empty($postData['type']))
            $this->helper->log('fatal', 'notification > webhook | Invalid Webhook Data', $postData);

        // Get order and transaction data
        $cartId = $postData['data']['subscriber']['reference'];
        $order  = $this->orderHelper->getOrderByCartId($cartId, true);
        $data   = $this->transaction->formatData($postData['data']);
        
        // Get subscription and subscriber from uid
        $subscription = $this->helper->getSubscriptionByUid($postData['data']['subscription']['uid']);
        $subscriber   = $this->helper->getSubscriberByUid($postData['data']['subscriber']['uid']);
        
        if (!$subscription || !$subscriber)
            $this->helper->log('error', 'Subscription or subscriber cannot be loaded', $postData);

        switch ($postData['type']) {
            case 'subscription:registration':
                // Save registration data and update subscriber state
                $subscriber->register_data = json_encode($postData['data']);
                $subscriber->state = $postData['data']['context']['status'] == 'success';
                $subscriber->update();

                // Save transaction to show data in order widget
                $data['total']        = $subscription->total;
                $data['order_status'] = (int) Configuration::get($subscriber->state ? 'PS_OS_PAYMENT' : 'PS_OS_ERROR');
                $transaction->saveTransaction($cartId, $data);

                // Continue only if validation was approved
                if (!$subscriber->state)
                    break;

                // Execute the first charge
                $subscriber->execute();

                // If Order exists
                if ($order) {
                    // Update payment method name
                    $order->payment = $postData['data']['source']['name'];

                    // Update order status only if it was not updated recently
                    if ($order->getCurrentState() != $data['order_status']) {
                        $order->setCurrentState($data['order_status']);
                        $this->orderUpdate->removeExpirationTasks($order);
                        $this->orderUpdate->updateOrderPayment($order, $data);
                    }

                    $order->update();
                } else {
                    // Create and validate Order
                    $order = $this->orderHelper->createOrder($cartId, $data['order_status'], $data['source_name'], \Module::getInstanceByName('mobbex'));

                    if ($order)
                        $this->orderUpdate->updateOrderPayment($order, $data);
                }

                break;
            case 'subscription:execution':
                $dates = $subscription->calculateDates();

                $execution = new MobbexExecution(
                    $postData['data']['execution']['uid'],
                    $subscription->uid,
                    $subscriber->uid,
                    $data['order_status'],
                    $data['total'],
                    $dates['current'],
                    $data['data']
                );
                $execution->save();

                // Update execution dates
                $subscriber->last_execution = $dates['current'];
                $subscriber->next_execution = $dates['next'];

                if (!$subscriber->start_date || strtotime($subscriber->start_date) < 0)
                    $subscriber->start_date = $subscriber->last_execution;

                $subscriber->update();
                break;
        }

        die('OK: ' . \Mobbex\PS\Checkout\Models\Config::MODULE_VERSION);
    }
}