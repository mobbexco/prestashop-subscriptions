<?php

defined('_PS_VERSION_') || exit;

// Main module classes
include_once dirname(__FILE__) . '/../mobbex/classes/Api.php';
include_once dirname(__FILE__) . '/../mobbex/classes/Model.php';
include_once dirname(__FILE__) . '/../mobbex/classes/Updater.php';
include_once dirname(__FILE__) . '/../mobbex/classes/Exception.php';
include_once dirname(__FILE__) . '/../mobbex/classes/MobbexHelper.php';

// Subscription classes
require_once dirname(__FILE__) . '/classes/Helper.php';
require_once dirname(__FILE__) . '/classes/Subscription.php';
require_once dirname(__FILE__) . '/classes/Subscriber.php';

class Mobbex_Subscriptions extends Module
{
    /** @var \Mobbex\Updater */
    public $updater;

    /** Module indentifier */
    public $name = 'mobbex_subscriptions';

    /** Module version */
    public $version = '1.0.0';

    /** Compatibility range */
    public $ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];

    /** Controllers availables */
    public $controllers = [];

    /** Display data */
    public $author           = 'Mobbex Co';
    public $displayName      = 'Mobbex Subscriptions';
    public $description      = 'Plugin de pago que provee la funcionalidad de suscripciones';
    public $confirmUninstall = '¿Seguro que desea desinstalar el módulo?';
    public $tab              = 'payments_gateways';

    public function __construct()
    {
        $this->checkDependencies();
        parent::__construct();

        if ($this->warning)
            return;

        $this->helper  = new \Mobbex\Subscriptions\Helper;
        $this->updater = new \Mobbex\Updater('mobbexco/prestashop-subscriptions');
    }

    public function checkDependencies()
    {
        if (!class_exists('\\Mobbex\\Model'))
            $this->warning = 'Es necesario que el módulo principal de Mobbex esté instalado.';

        if (!extension_loaded('curl'))
            $this->warning = 'Es necesario que la extensión cURL esté habilitada.';
    }

    public function install()
    {
        // Get install query from sql file
        $sql = str_replace(['PREFIX_', 'ENGINE_TYPE'], [_DB_PREFIX_, _MYSQL_ENGINE_], file_get_contents(dirname(__FILE__) . '/install.sql'));

        return !$this->warning && DB::getInstance()->execute($sql) && parent::install() && $this->registerHooks();
    }

    /**
     * Register module hooks dependig on prestashop version.
     * 
     * @return bool Result of the registration
     */
    public function registerHooks()
    {
        $hooks = [
            'displayMobbexConfiguration',
            'displayMobbexProductSettings',
            'displayMobbexCategorySettings',
            'actionProductUpdate',
            'actionMobbexProcessPayment',
        ];

        foreach ($hooks as $hookName) {
            if (!$this->registerHook($hookName))
                return false;
        }

        return true;
    }

    public function hookDisplayMobbexConfiguration($form)
    {
        $form = array_merge_recursive($form, [
            'form' => [
                'tabs'  => [
                    'tab_subscriptions' => $this->l('Suscripciones'),
                ],
                'input' => [
                    [
                        'name'     => 'MOBBEX_SUBSCRIPTION_TYPE',
                        'type'     => 'radio',
                        'required' => false,
                        'label'    => $this->l('Controlador de suscripciones'),
                        'tab'      => 'tab_subscriptions',
                        'values'  => [
                            [
                                'id'    => 'm_subtype_mx',
                                'value' => 'dynamic',
                                'label' => 'Mobbex',
                                'p'     => 'La recurrencia de los pagos será controlada por el servicio.'
                            ],
                            [
                                'id'    => 'm_subtype_ps',
                                'value' => 'manual',
                                'label' => 'Módulo',
                                'p'     => 'La recurrencia de los pagos será controlada por el módulo.'
                            ],
                        ],
                    ],
                ]
            ]
        ]);

        return $form;
    }

    public function hookDisplayMobbexProductSettings($params)
    {
        $subscription = new \MobbexSubscription($params['id']);

        $this->context->smarty->assign([
            'subscription_type' => \Configuration::get('MOBBEX_SUBSCRIPTION_TYPE') != 'manual' ? 'dynamic' : 'manual',
            'subscription_mode' => (bool) $subscription->uid,
            'charge_interval'   => preg_replace('/[^0-9]/', '', (string) $subscription->interval) ?: 1,
            'charge_period'     => preg_replace('/[0-9]/', '', (string) $subscription->interval) ?: 'm',
            'free_trial'        => $subscription->free_trial,
            'signup_fee'        => $subscription->signup_fee,
        ]);

        return $this->display(__FILE__, 'views/product-settings.tpl');
    }

    public function hookActionProductUpdate($params)
    {
        // Exit if is bulk import
        if (strnatcasecmp(Tools::getValue('controller'), 'adminImport') === 0)
            return;

        // Get and validate values
        $subscription_mode = !empty($_POST['subscription_mode']) && $_POST['subscription_mode'] == 'yes';
        $charge_interval   = !empty($_POST['charge_interval']) && is_numeric($_POST['charge_interval']) ? $_POST['charge_interval'] : 1;
        $charge_period     = !empty($_POST['charge_period']) && in_array($_POST['charge_period'], ['d', 'w', 'm', 'y']) ? $_POST['charge_period'] : 'm';
        $free_trial        = !empty($_POST['free_trial']) && is_numeric($_POST['free_trial']) ? $_POST['free_trial'] : 0;
        $signup_fee        = !empty($_POST['signup_fee']) && is_numeric($_POST['signup_fee']) ? $_POST['signup_fee'] : 0;

        if (!$subscription_mode)
            return;

        // Create subscription
        $subscription = new \MobbexSubscription(
            $params['id_product'],
            Configuration::get('MOBBEX_SUBSCRIPTION_TYPE') != 'manual' ? 'dynamic' : 'manual',
            $params['product']->getPrice(),
            $params['product']->name[$this->context->language->id],
            $params['product']->description_short[$this->context->language->id], // Or re-build product using language id
            0,
            $charge_interval . $charge_period,
            $free_trial,
            $signup_fee
        );

        return $subscription->save();
    }

    public function hookActionMobbexProcessPayment($cart)
    {
        // Load subscription from cart
        $subscription = $this->helper->getSubscriptionFromCart($cart);

        if (!$subscription)
            throw new \Mobbex\Exception('Mobbex Error: No Subscriptions in cart');

        // Get customer data
        $customer = \MobbexHelper::getCustomer($cart);

        // Create subscriber
        $subscriber = new \MobbexSubscriber(
            $cart->id,
            $subscription->uid,
            (bool) \Configuration::get('MOBBEX_TEST_MODE'),
            $customer['name'],
            $customer['email'],
            $customer['phone'],
            $customer['identification'],
            $customer['uid'] ?: null
        );
        $subscriber->save();

        if (!$subscriber->uid)
            throw new \Mobbex\Exception('Mobbex Error: Subscriber creation failed');

        return [
            'id'         => $subscription->uid,
            'sid'        => $subscriber->uid,
            'url'        => $subscriber->source_url,
            'return_url' => $this->helper->getUrl('notification', 'callback', ['product_id' => $subscription->product_id])
        ];
    }
}