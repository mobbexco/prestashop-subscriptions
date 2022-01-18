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
}