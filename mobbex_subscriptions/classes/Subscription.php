<?php

namespace Mobbex;

class Subscription extends \ObjectModel
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
     * Load/build the Subscription from product id.
     * 
     * @param mixed $args Parent ObjectModel constructor args.
     */
    public function __construct(...$args)
    {
        $this->api    = new \Mobbex\Api;
        $this->helper = new \Mobbex\Subscriptions\Helper;

        parent::__construct(...$args);
    }

    /**
     * Create a Subscription using Mobbex API.
     * 
     * @return string|null UID if created correctly.
     */
    public function create()
    {
        $data = [
            'uri'    => 'subscriptions/' . $this->uid,
            'method' => 'POST',
            'body'   => [
                'total'       => $this->total,
                'currency'    => 'ARS',
                'type'        => $this->type,
                'reference'   => 'ps_product_id:' . $this->product_id,
                'name'        => $this->name,
                'description' => $this->description,
                'webhook'     => $this->helper->getUrl('notification', 'webhook', ['product_id' => $this->product_id]),
                'return_url'  => $this->helper->getUrl('notification', 'callback', ['product_id' => $this->product_id]),
                'limit'       => $this->limit,
                'setupFee'    => $this->signup_fee,
                'interval'    => $this->interval,
                'trial'       => $this->free_trial,
                'options'     => \MobbexHelper::getOptions()
            ]
        ];

        try {
            return $this->api->request($data)['uid'];
        } catch (\Exception $e) {
            \PrestaShopLogger::addLog('Mobbex Subscription Create/Update Error: ' . $e->getMessage(), 3, null, 'Mobbex', $this->product_id, true);
        }
    }

    /**
     * Save/update data to db creating subscription from Mobbex API.
     * 
     * @param bool $null_values
     * @param bool $auto_date
     * 
     * @return bool True if saved correctly.
     */
    public function save($null_values = false, $auto_date = true)
    {
        $uid = $this->create();

        // Try to save uid
        if ($uid)
            $this->uid = $uid;

        return $uid && parent::save($null_values, $auto_date);
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