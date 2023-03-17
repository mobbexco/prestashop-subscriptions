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
        $custom_logo = \Configuration::get(\Mobbex\PS\Checkout\Models\Helper::K_THEME_LOGO);

        // If store's logo option is disabled, use the one configured in mobbex
        $default_logo = null;
        if (!empty(\Configuration::get(\Mobbex\PS\Checkout\Models\Helper::K_THEME_SHOP_LOGO))) {
            $default_logo = \Tools::getShopDomainSsl(true, true) . _PS_IMG_ . \Configuration::get('PS_LOGO');
        }

        $theme_background = \Configuration::get(\Mobbex\PS\Checkout\Models\Helper::K_THEME_BACKGROUND);
        $theme_primary = \Configuration::get(\Mobbex\PS\Checkout\Models\Helper::K_THEME_PRIMARY);

        $theme = array(
            "type" => \Configuration::get(\Mobbex\PS\Checkout\Models\Helper::K_THEME) ?: \Mobbex\PS\Checkout\Models\Helper::K_DEF_THEME,
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
            'button' => (\Configuration::get(\Mobbex\PS\Checkout\Models\Helper::K_EMBED) == true),
            'domain' => \Context::getContext()->shop->domain,
            "theme" => $theme,
            // Will redirect automatically on Successful Payment Result
            "redirect" => [
                "success" => true,
                "failure" => false,
            ],
            "platform" => \Mobbex\PS\Checkout\Models\Helper::getPlatform(),
        );

        return $options;
    }
}