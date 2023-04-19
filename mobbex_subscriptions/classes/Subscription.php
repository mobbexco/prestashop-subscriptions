<?php

class MobbexSubscription extends \Mobbex\PS\Checkout\Models\Model
{
    /** @var \Mobbex\Api */
    public $api;

    /** @var \Mobbex\Subscriptions\Helper */
    public $helper;

    public $periods = [
        'd' => 'day',
        'm' => 'month',
        'y' => 'year',
    ];

    public $product_id;
    public $uid;
    public $type;
    public $state;
    public $interval;
    public $name;
    public $description;
    public $total;
    public $limit;
    public $free_trial;
    public $signup_fee;

    public $fillable = [
        'type',
        'total',
        'name',
        'description',
        'limit',
        'interval',
        'free_trial',
        'signup_fee'
    ];

    public static $definition = [
        'table'     => 'mobbex_subscription',
        'primary'   => 'product_id',
        'multilang' => false,
        'fields' => [
            'product_id' => [
                'type'     => self::TYPE_INT,
                'required' => false
            ],
            'uid' => [
                'type'     => self::TYPE_STRING,
                'required' => false
            ],
            'type' => [
                'type'     => self::TYPE_STRING,
                'required' => false
            ],
            'state' => [
                'type'     => self::TYPE_INT,
                'required' => false
            ],
            'interval' => [
                'type'     => self::TYPE_STRING,
                'required' => false
            ],
            'name' => [
                'type'     => self::TYPE_STRING,
                'required' => false
            ],
            'description' => [
                'type'     => self::TYPE_STRING,
                'required' => false
            ],
            'total' => [
                'type'     => self::TYPE_FLOAT,
                'required' => false
            ],
            'limit' => [
                'type'     => self::TYPE_INT,
                'required' => false
            ],
            'free_trial' => [
                'type'     => self::TYPE_INT,
                'required' => false
            ],
            'signup_fee' => [
                'type'     => self::TYPE_FLOAT,
                'required' => false
            ],
        ],
    ];

    /**
     * Build a Subscription from product id.
     * 
     * @param int|null $product_id
     * @param string|null $type "manual" | "dynamic"
     * @param int|float|null $total Amount to charge.
     * @param string|null $name
     * @param string|null $description
     * @param int|null $limit Maximum number of executions.
     * @param string|null $interval Interval between executions.
     * @param int|null $freeTrial Number of free periods.
     * @param int|float|null $signupFee Different initial amount.
     */
    public function __construct(
        $product_id = null,
        $type = null,
        $total = null,
        $name = null,
        $description = null,
        $limit = null,
        $interval = null,
        $freeTrial = null,
        $signupFee = null
    ) {
        $this->api    = new \Mobbex\Api;
        $this->helper = new \Mobbex\Subscriptions\Helper;

        parent::__construct(...func_get_args());
    }

    /**
     * Create a Subscription using Mobbex API.
     * 
     * @return string|null UID if created correctly.
     */
    public function create()
    {
        try {
            $response = new \Mobbex\Modules\Subscription(
                $this->product_id,
                $this->uid,
                $this->type,
                $this->helper->getUrl('notification', 'callback', ['product_id' => $this->product_id]),
                $this->helper->getUrl('notification', 'webhook', ['product_id' => $this->product_id]),
                $this->total,
                $this->name,
                $this->description,
                $this->interval,
                $this->limit,
                $this->free_trial,
                $this->signup_fee
            );

            return isset($response->uid) ? $response->uid : $this->uid;
        } catch (\Mobbex\Exception $e) {
            \PrestaShopLogger::addLog('Mobbex Subscription Create/Update Error: ' . $e->getMessage(), 3, null, 'Mobbex', $this->product_id, true);
        }
    }

    /**
     * Save/update data to db creating subscription from Mobbex API.
     * 
     * @param bool $nullValues
     * @param bool $autoDate
     * 
     * @return bool True if saved correctly.
     */
    public function save($nullValues = false, $autoDate = true)
    {
        $uid = $this->create();

        // Try to save uid
        if ($uid)
            $this->uid = $uid;

        return $uid && parent::save($nullValues, $autoDate);
    }

    /**
     * Calculate execution dates from Subscription interval.
     * 
     * @return string[]
     */
    public function calculateDates()
    {
        $interval = preg_replace('/[^0-9]/', '', (string) $this->interval) ?: 1;
        $period   = $this->periods[preg_replace('/[0-9]/', '', (string) $this->interval) ?: 'm'];

        return [
            'current' => date('Y-m-d H:i:s'),
            'next'    => date('Y-m-d H:i:s', strtotime("+ $interval $period"))
        ];
    }
}