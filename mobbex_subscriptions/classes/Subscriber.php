<?php

class MobbexSubscriber extends \Mobbex\Model
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
            'test'  => [
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
     */
    public function __construct(
        $cartId = null,
        $subscriptionUid = null,
        $test = null,
        $name = null,
        $email = null,
        $phone = null,
        $identification = null
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

        $data = [
            'uri'    => 'subscriptions/' . $this->subscription_uid . '/subscriber',
            'method' => 'POST',
            'body'   => [
                'total'     => $subscription->total,
                'reference' => $this->cart_id,
                'test'      => $this->test,
                'startDate' => [
                    'day'   => date('d', strtotime($dates['next'])),
                    'month' => date('m', strtotime($dates['next'])),
                    'year'  => date('y', strtotime($dates['next'])),
                ],
                'customer'  => [
                    'name'           => $this->name,
                    'email'          => $this->email,
                    'identification' => $this->identification,
                ]
            ]
        ];

        try {
            return $this->api->request($data);
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog('Mobbex Subscription Create/Update Error: ' . $e->getMessage(), 3, null, 'Mobbex', $this->product_id, true);
        }
    }

    /**
     * Execute a charge manually.
     * 
     * @return bool Result of execution request.
     */
    public function execute()
    {
        try {
            return !$this->api->request([
                'uri'    => 'subscriptions/' . $this->subscription_uid . '/subscriber/' . $this->uid . '/execution',
                'method' => 'GET',
            ]);;
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog('Mobbex Subscription Create/Update Error: ' . $e->getMessage(), 3, null, 'Mobbex', $this->product_id, true);
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
        if ($result) {
            $this->uid         = $result['uid'];
            $this->source_url  = $result['sourceUrl'];
            $this->control_url = $result['subscriberUrl'];
        }

        return $result && parent::save($null_values, $auto_date);
    }
}