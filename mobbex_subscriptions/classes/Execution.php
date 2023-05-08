<?php

class MobbexExecution extends \Mobbex\PS\Checkout\Models\Model
{
    public $uid;
    public $subscription_uid;
    public $subscriber_uid;
    public $status;
    public $total;
    public $date;
    public $data;

    public $fillable = [
        'subscription_uid',
        'subscriber_uid',
        'status',
        'total',
        'date',
        'data',
    ];

    public static $definition = [
        'table'     => 'mobbex_execution',
        'primary'   => 'uid',
        'multilang' => false,
        'fields' => [
            'uid' => [
                'type'     => self::TYPE_STRING,
                'required' => false
            ],
            'subscription_uid' => [
                'type'     => self::TYPE_STRING,
                'required' => false
            ],
            'subscriber_uid' => [
                'type'     => self::TYPE_STRING,
                'required' => false
            ],
            'status' => [
                'type'     => self::TYPE_INT,
                'required' => false
            ],
            'total' => [
                'type'     => self::TYPE_FLOAT,
                'required' => false
            ],
            'date' => [
                'type'     => self::TYPE_DATE,
                'required' => false
            ],
            'data' => [
                'type'     => self::TYPE_STRING,
                'required' => false
            ],
        ],
    ];

    /**
     * Build a Execution from UID.
     * 
     * @param string|null $uid The UID of the execution.
     * @param string|null $subscriptionUid 
     * @param string|null $subscriberUid 
     * @param int|null $status Current satus identifier.
     * @param int|float|null $total Amount charged.
     * @param string|null $date
     * @param string|null $data Webhook encoded data.
     */
    public function __construct(
        $uid = null,
        $subscriptionUid = null,
        $subscriberUid = null,
        $status = null,
        $total = null,
        $date = null,
        $data = null
    ) {
        parent::__construct(...func_get_args());
    }

    /**
     * Get Mobbex execution from DB with the uid
     * 
     * @param int $uid
     * @return array
     */
    public static function getData($uid)
    {
        $execution = \Db::getInstance()->executes('SELECT * FROM ' . _DB_PREFIX_ . 'mobbex_execution' . ' WHERE uid = "' . $uid . '";');
        return !empty($execution[0]) ? json_decode($execution[0]['data'], true) : false;
    }
}