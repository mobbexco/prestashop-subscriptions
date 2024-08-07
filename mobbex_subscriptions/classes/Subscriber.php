<?php

class MobbexSubscriber extends \Mobbex\PS\Checkout\Models\Model
{
    /** @var \Mobbex\Api */
    public $api;

    /** @var \Mobbex\Subscriptions\Helper */
    public $helper;

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
        $this->helper = new \Mobbex\Subscriptions\Helper;

        parent::__construct(...func_get_args());
    }

    /**
     * Create a Subscriber using Mobbex API.
     * 
     * @return string|null UID if created correctly.
     */
    public function create()
    {
        $subscription = $this->helper->getSubscriptionByUid($this->subscription_uid);
        $dates = $subscription->calculateDates();

        $customer = [
                    'name'           => $this->name,
                    'email'          => $this->email,
                    'phone'          => $this->phone,
                    'identification' => $this->identification,
                    'uid'            => $this->customer_id,
        ];

        try {
            return new \Mobbex\Modules\Subscriber(
                (string) $this->cart_id,
                $this->uid,
                $this->subscription_uid,
                $dates['next'],
                $customer,
                $subscription->total,
            );
        } catch (\Mobbex\Exception $e) {
            \PrestaShopLogger::addLog('Mobbex Subscriber Create/Update Error: ' . $e->getMessage(), 3, null, 'Mobbex', $this->cart_id, true);
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
     * Save/update data to db creating subscriber from Mobbex API.
     * 
     * @param bool $null_values
     * @param bool $auto_date
     * 
     * @return bool True if saved correctly.
     */
    public function save($null_values = false, $auto_date = true)
    {
        $result = $this->create();

        // Try to save data
        $this->uid         = $result->uid ?: $this->uid;
        $this->source_url  = $result->sourceUrl ?: $this->source_url;
        $this->control_url = $result->controlUrl ?: $this->control_url;

        // Remember, Mobbex returns an empty array on success edit
        return ($result || $result == []) && parent::save($null_values, $auto_date);
    }
}