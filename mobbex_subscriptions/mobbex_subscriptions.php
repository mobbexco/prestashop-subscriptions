<?php

defined('_PS_VERSION_') || exit;

require_once dirname(__FILE__) . '/classes/Helper.php';
require_once dirname(__FILE__) . '/../mobbex/classes/Api.php';
require_once dirname(__FILE__) . '/../mobbex/classes/Updater.php';
require_once dirname(__FILE__) . '/../mobbex/classes/Exception.php';
require_once dirname(__FILE__) . '/../mobbex/classes/MobbexHelper.php';
require_once dirname(__FILE__) . '/../mobbex/classes/MobbexTransaction.php';
require_once dirname(__FILE__) . '/../mobbex/classes/MobbexCustomFields.php';

class Mobbex_Subscriptions extends Module
{
    /** @var \Mobbex\Api */
    public $api;

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

    public function __construct()
    {
        $this->api     = new \Mobbex\Api();
        $this->updater = new \Mobbex\Updater('mobbexco/prestashop-subscriptions');

        $this->checkDependencies();
        parent::__construct();
    }

    public function checkDependencies()
    {
        if (!extension_loaded('curl'))
            $this->_errors[] = 'Mobbex Subscriptions requiere la extensión cUR para funcionar correctamente';
    }

    public function install()
    {
        return parent::install() && $this->registerHooks();
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

        $ps17Hooks = $ps16Hooks = [];

        // Merge current version hooks with common hooks
        $hooks = array_merge($hooks, _PS_VERSION_ > '1.7' ? $ps17Hooks : $ps16Hooks);

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