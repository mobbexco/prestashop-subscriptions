<?php

class MobbexSubscriber extends \Mobbex\PS\Checkout\Models\Model
{
    /** @var \Mobbex\Api */
    public $api;

    /** @var \Mobbex\Subscriptions\Helper */
    public $helper;

    /** @var \Mobbex\PS\Checkout\Models\Config */
    public $config;

    public $cart_id;
    public $uid;
    public $subscription_uid;
    public $state;
    public $test;
    public $name;
    public $email;
    public $phone;
    public $identification;
    public $customer_id;
    public $source_url;
    public $control_url;
    public $register_data;
    public $start_date;
    public $last_execution;
    public $next_execution;

    public $fillable = [
        'subscription_uid',
        'test',
        'name',
        'email',
        'phone',
        'identification',
        'customer_id',
    ];

    public static $definition = [
        'table'     => 'mobbex_subscriber',
        'primary'   => 'cart_id',
        'multilang' => false,
        'fields' => [
            'cart_id' => [
                'type'     => self::TYPE_INT,
                'required' => false
            ],
            'uid' => [
                'type'     => self::TYPE_STRING,
                'required' => false
            ],
            'subscription_uid' => [
                'type'     => self::TYPE_STRING,
                'required' => false
            ],
            'state' => [
                'type'     => self::TYPE_INT,
                'required' => false
            ],
            'test' => [
                'type'     => self::TYPE_INT,
                'required' => false
            ],
            'name' => [
                'type'     => self::TYPE_STRING,
                'required' => false
            ],
            'email' => [
                'type'     => self::TYPE_STRING,
                'required' => false
            ],
            'phone' => [
                'type'     => self::TYPE_STRING,
                'required' => false
            ],
            'identification' => [
                'type'     => self::TYPE_STRING,
                'required' => false
            ],
            'customer_id' => [
                'type'     => self::TYPE_INT,
                'required' => false
            ],
            'source_url' => [
                'type'     => self::TYPE_STRING,
                'required' => false
            ],
            'control_url' => [
                'type'     => self::TYPE_STRING,
                'required' => false
            ],
            'register_data' => [
                'type'     => self::TYPE_STRING,
                'required' => false
            ],
            'start_date' => [
                'type'     => self::TYPE_DATE,
                'required' => false
            ],
            'last_execution' => [
                'type'     => self::TYPE_DATE,
                'required' => false
            ],
            'next_execution' => [
                'type'     => self::TYPE_DATE,
                'required' => false
            ],
        ],
    ];

    /**
     * Build a Subscriber from cart id.
     * 
     * @param int|null $cartId
     * @param string|null $subscriptionUid
     * @param bool|null $test Enable test mode for this subscriber.
     * @param string|null $name
     * @param string|null $email
     * @param string|null $phone
     * @param string|null $identification Tax-ID or DNI of the customer.
     * @param int|null $customerId
     */
    public function __construct(
        $cartId = null,
        $subscriptionUid = null,
        $test = null,
        $name = null,
        $email = null,
        $phone = null,
        $identification = null,
        $customerId = null
    ) {
        $this->api    = new \Mobbex\Api;
        $this->config = new \Mobbex\PS\Checkout\Models\Config();
        $this->helper = new \Mobbex\Subscriptions\Helper;

        $this->api::init(
            $this->config->settings['api_key'], 
            $this->config->settings['access_token']
        );
        
        parent::__construct(...func_get_args());
    }

    /**
     * Get a Subscriber from Mobbex API.
     * 
     * @param array $subscriberUid required subscriber uid
     * 
     * @return \MobbexSubscriber|null Mobbex Subscriber
     */
    public function get($subscriberUid)
    {
        try {
            return $this->api::request([
                'method' => 'GET',
                'uri'    => 'subscriptions/' . $this->subscription_uid . '/subscriber/' . $subscriberUid,
            ]);
        } catch (\Mobbex\Exception $e) {
            \PrestaShopLogger::addLog('Get Mobbex Subscriber Error > Mobbexsubscriber->get(): ' . $e->getMessage(), 3, null, 'Mobbex', $subscriberUid, true);
        }
    }

    /**
     * Execute a charge manually.
     * 
     * @return bool Result of execution request.
     */
    public function execute()
    {
        $subscription = $this->helper->getSubscriptionByUid($this->subscription_uid);

        try {
            return new \Mobbex\Modules\Subscriber(
                (string) $this->cart_id,
                $this->uid,
                $this->subscription_uid,
                $this->customer_id,
                $subscription->total,
            );
        } catch (\Mobbex\Exception $e) {
            \PrestaShopLogger::addLog('Mobbex Subscription Execution Error: ' . $e->getMessage(), 3, null, 'Mobbex', $this->cart_id, true);
        }

        return false;
    }

    /**
     * Save/update data to db getting subscriber from Mobbex API.
     * 
     * @param bool $null_values
     * @param bool $auto_date
     * 
     * @return bool True if saved correctly.
     */
    public function save($subscriberUid = null , $null_values = false, $auto_date = true)
    {
        // Gets subscriber and set its properties
        $result = $this->get($subscriberUid);
        $this->setSubscriber($result);
        
        // Remember, Mobbex returns an empty array on success edit
        return ($result || $result == []) && parent::save($null_values, $auto_date);
    }

    /**
     * Sets MobbexSubscriber properties with response
     * 
     * @param array $result
     */
    public function setSubscriber($result)
    {
        // Gets subscription data
        $subscription = $this->helper->getSubscriptionByUid($this->subscription_uid);
        $dates        = $subscription->calculateDates();

        // Sets class properties
        $this->control_url    = isset($result['url']) ? $result['url'] : '';
        $this->next_execution = isset($dates['next']) ? $dates['next'] : '';
        $this->start_date     = isset($dates['current']) ? $dates['current'] : '';
        $this->source_url     = isset($result['url']) ? $result['url'] . '/source' : '';
        $this->uid            = isset($result['subscriber']['uid']) ? $result['subscriber']['uid'] : '';
        $this->state          = isset($result['subscriber']['status']) ? $result['subscriber']['status'] : '';
        $this->customer_id    = isset($result['subscriber']['customerData']['uid']) ? $result['subscriber']['customerData']['uid'] : '';
        $this->name           = isset($result['subscriber']['customerData']['name']) ? $result['subscriber']['customerData']['name'] : '';
        $this->register_data  = isset($result['subscriber']['executions'][0]) ? json_encode($result['subscriber']['executions'][0]) : '';
        $this->email          = isset($result['subscriber']['customerData']['email']) ? $result['subscriber']['customerData']['email'] : '';
        $this->phone          = isset($result['subscriber']['customerData']['phone']) ? $result['subscriber']['customerData']['phone'] : '';
        $this->identification = isset($result['subscriber']['customerData']['identification']) ? $result['subscriber']['customerData']['identification'] : '';
    }
}