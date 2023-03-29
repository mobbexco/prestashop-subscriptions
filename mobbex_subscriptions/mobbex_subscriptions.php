<?php

defined('_PS_VERSION_') || exit;

// Main module classes
include_once dirname(__FILE__) . '/../mobbex/Models/AbstractModel.php';
include_once dirname(__FILE__) . '/../mobbex/Models/Model.php';
include_once dirname(__FILE__) . '/../mobbex/Models/Updater.php';
include_once dirname(__FILE__) . '/../mobbex/Models/OrderUpdate.php';
include_once dirname(__FILE__) . '/../mobbex/Models/Helper.php';
include_once dirname(__FILE__) . '/../mobbex/Models/Transaction.php';
include_once dirname(__FILE__) . '/../mobbex/Models/Logger.php';
include_once dirname(__FILE__) . '/../mobbex/Models/Config.php';
include_once dirname(__FILE__) . '/../mobbex/Models/Registrar.php';
include_once dirname(__FILE__) . '/../mobbex/Models/Task.php';

// Subscription classes
require_once dirname(__FILE__) . '/classes/Api.php';
require_once dirname(__FILE__) . '/classes/Exception.php'; 
require_once dirname(__FILE__) . '/classes/Helper.php';
require_once dirname(__FILE__) . '/classes/Subscription.php';
require_once dirname(__FILE__) . '/classes/Subscriber.php';
require_once dirname(__FILE__) . '/classes/Execution.php';

class Mobbex_Subscriptions extends Module
{
    /** @var \Mobbex\PS\Checkout\Models\Updater */
    public $updater;

    /** @var \Mobbex\Subscriptions\Helper */
    public $helper;

    /** Module indentifier */
    public $name = 'mobbex_subscriptions';

    /** Module version */
    public $version = '1.0.0';

    /** Compatibility range */
    public $ps_versions_compliancy = ['min' => '1.6', 'max' => _PS_VERSION_];

    /** Controllers availables */
    public $controllers = ['notification'];

    /** Display data */
    public $author           = 'Mobbex Co';
    public $displayName      = 'Mobbex Subscriptions';
    public $description      = 'Plugin de pago que provee la funcionalidad de suscripciones';
    public $confirmUninstall = '¿Seguro que desea desinstalar el módulo?';
    public $tab              = 'payments_gateways';

    
    public function __construct()
    {
        $this->checkDependencies();
        $this->helper  = new \Mobbex\Subscriptions\Helper;
        $this->updater = new \Mobbex\PS\Checkout\Models\Updater('mobbexco/prestashop-subscriptions');

        parent::__construct();

        if (!empty($this->warning))
            return;
    }

    public function checkDependencies()
    {
        if (!class_exists('\\Mobbex\\Models\\Model'))
            $this->warning = 'Es necesario que el módulo principal de Mobbex esté instalado.';

        if (!extension_loaded('curl'))
            $this->warning = 'Es necesario que la extensión cURL esté habilitada.';
    }

    public function install()
    {
        try {
            // First try to create each table
            foreach (['execution', 'subscriber', 'subscription'] as $table) {
                $query = str_replace(
                    ['DB_PREFIX_', 'ENGINE_TYPE'],
                    [_DB_PREFIX_, _MYSQL_ENGINE_],
                    file_get_contents(dirname(__FILE__) . "/sql/$table.sql")
                );

                if (!DB::getInstance()->execute($query))
                    return false;
            }

            return parent::install()
                && $this->unregisterHooks()
                && $this->registerHooks();
        } catch (\Mobbex\Subscriptions\Exception $e) { 
            $this->helper->log('debug', 'Error on Install Mobbex Subscriptions: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Try to update the module.
     * 
     * @return bool Result of update.
     */
    public function runUpdate()
    {
        try {
            return !$this->updater->updateVersion($this, true);
        } catch (\PrestaShopException $e) {
            $this->helper->log('debug', 'Mobbex Subscriptions Update Error: ' . $e->getMessage());
        }
        return false;
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
 
    /**
     * Unregister all current module hooks.
     * 
     * @return bool Result.
     */
    public function unregisterHooks()
    {
        // Get hooks used by module
        $hooks = \Db::getInstance()->executeS(
            'SELECT DISTINCT(`id_hook`) FROM `' . _DB_PREFIX_ . 'hook_module` WHERE `id_module` = ' . $this->id
        ) ?: [];

        foreach ($hooks as $hook) {
            if (!$this->unregisterHook($hook['id_hook']) || !$this->unregisterExceptions($hook['id_hook']))
                return false;
        }

        return true;
    }

    public function hookDisplayMobbexConfiguration($form)
    {
        /*$form = array_merge_recursive($form, [
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
        ]);*/

        // Run update if is possible
        if (!empty($_GET['run_subs_update']))
            $this->runUpdate() && Tools::redirectAdmin(\Mobbex\PS\Checkout\Models\Helper::getUpgradeURL());

        // Add update message
        $this->updater = new \Mobbex\PS\Checkout\Models\Updater('mobbexco/prestashop-subscriptions');     
        if (empty($_GET['run_subs_update']) && $this->updater->hasUpdates($this->version))
            $form['form']['description'] = "¡Nueva actualización disponible! Haga <a href='$_SERVER[REQUEST_URI]&run_subs_update=1'>clic aquí</a> para actualizar Mobbex Subscriptions a la versión " . $this->updater->latestRelease['tag_name'];

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
            throw new \Mobbex\Subscriptions\Exception('Mobbex Error: No Subscriptions in cart');

        // Get customer data
        $customer = \Mobbex\PS\Checkout\Models\Helper::getCustomer($cart);

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
            throw new \Mobbex\Subscriptions\Exception('Mobbex Error: Subscriber creation failed');

        // Save suscriber cart id on a cookie to use later on callback
        Context::getContext()->cookie->subscriber_cart_id = $cart->id;

        return [
            'id'         => $subscription->uid,
            'sid'        => $subscriber->uid,
            'url'        => $subscriber->source_url,
            'return_url' => $this->helper->getUrl('notification', 'callback', ['product_id' => $subscription->product_id])
        ];
    }
}