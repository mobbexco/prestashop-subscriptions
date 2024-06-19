<?php

namespace Mobbex\Subscriptions;

class Helper
{
    /**
     * Retrieve endpoint URL for a given module controller.
     * 
     * @param string $controller
     * @param string $action
     * @param array $extraParams An asociative array with extra query params.
     * 
     * @return string
     */
    public function getUrl($controller, $action, $extraParams = [])
    {
        return \Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'index.php?' . http_build_query(array_merge([
            'fc'         => 'module',
            'module'     => 'mobbex_subscriptions',
            'controller' => $controller,
            'action'     => $action
        ], $extraParams));
    }

    /**
     * Get a Subscription using UID.
     * 
     * @param string $uid
     * 
     * @return \MobbexSubscription|null
     */
    public function getSubscriptionByUid($uid)
    {
        $product_id = \Db::getInstance()->getValue('SELECT product_id FROM ' . _DB_PREFIX_ . "mobbex_subscription WHERE uid = '$uid'");
        return $product_id ? new \MobbexSubscription($product_id) : null;
    }

    /**
     * Get a Subscription from cart products if there is any.
     * 
     * @param Cart $cart
     * 
     * @return \MobbexSubscription|null
     */
    public function getSubscriptionFromCart($cart)
    {
        foreach ($cart->getProducts(true) as $product) {
            $subscription = new \MobbexSubscription($product['id_product']);

            if ($subscription->uid)
                return $subscription;
        }
    }

    /**
     * Get a Subscriber using UID.
     * 
     * @param string $uid
     * 
     * @return \MobbexSubscriber|null
     */
    public function getSubscriberByUid($uid)
    {
        $cart_id = \Db::getInstance()->getValue('SELECT cart_id FROM ' . _DB_PREFIX_ . "mobbex_subscriber WHERE uid = '$uid'");
        return $cart_id ? new \MobbexSubscriber($cart_id) : null;
    }

     public static function getOptions()
    {
        $config      = new \Mobbex\PS\Checkout\Models\Config;
        $custom_logo = isset($config->settings['theme_logo']) ? $config->settings['theme_logo'] : Tools::getShopDomainSsl(true, true) . _PS_IMG_ . \Configuration::get('PS_LOGO');

        // If store's logo option is disabled, use the one configured in mobbex
        $default_logo = null;
        if (!empty($config->settings['shop_theme_logo'])) {
            $default_logo = \Tools::getShopDomainSsl(true, true) . _PS_IMG_ . \Configuration::get('PS_LOGO');
        }

        $theme_background = $config->settings['background'];
        $theme_primary    = $config->settings['color'];

        $theme = array(
            "type" => $config->settings['theme'],
            "header" => [
                "name" => \Configuration::get('PS_SHOP_NAME'),
                "logo" => !empty($custom_logo) ? $custom_logo : $default_logo,
            ],
            'background' => !empty($theme_background) ? $theme_background : null,
            'colors' => [
                'primary' => !empty($theme_primary) ? $theme_primary : null,
            ],
        );

        $options = array(
            'button' => ($config->settings['embed'] == true),
            'domain' => \Context::getContext()->shop->domain,
            "theme" => $theme,
            // Will redirect automatically on Successful Payment Result
            "redirect" => [
                "success" => true,
                "failure" => false,
            ],
            "platform" => \Mobbex\Platform::toArray(),
        );

        return $options;
    }

   /**
     * Add log to PrestaShop log table.
     * Mode debug: Log data if debug mode is active
     * Mode error: Always log data.
     * Mode fatal: Always log data & stop code execution.
     * 
     * @param string $mode debug | error | fatal    
     * @param string $message
     * @param array $data
     * @param bool $die
     */
    public function log($mode, $message, $data = [])
    {
        if (!\Configuration::get('MOBBEX_DEBUG') && $mode === 'debug')
            return;

        \PrestaShopLogger::addLog(
            "Mobbex $mode: $message " . json_encode($data),
            $mode === 'error' ? 3 : 1,
            null,
            'Mobbex',
            str_replace('.', '', \Mobbex\PS\Checkout\Models\Config::MODULE_VERSION),
            true
        );

        if ($mode === 'fatal')
            die($message);
    }
}