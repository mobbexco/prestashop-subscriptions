<?php

defined('_PS_VERSION_') || exit;

// Subscription classes
require_once dirname(__FILE__) . '/classes/Helper.php';
require_once dirname(__FILE__) . '/classes/Subscription.php';
require_once dirname(__FILE__) . '/classes/Subscriber.php';

// Main module classes
include_once dirname(__FILE__) . '/../mobbex/classes/Api.php';
include_once dirname(__FILE__) . '/../mobbex/classes/Updater.php';
include_once dirname(__FILE__) . '/../mobbex/classes/Exception.php';
include_once dirname(__FILE__) . '/../mobbex/classes/MobbexHelper.php';

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
        if (!class_exists('\\Mobbex\\Api'))
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
            'actionProductUpdate'
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
}