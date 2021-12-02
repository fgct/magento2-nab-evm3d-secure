<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fgc\NabEvm3D\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Fgc\NabEvm3D\Helper\Data;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const JS_PRODUCTTION_SDK = 'https://transact.nab.com.au/threeds-js/securepay-threeds'; // remove ext .js
    const JS_SANBOX_SDK = 'https://demo.transact.nab.com.au/threeds-js/securepay-threeds';

    function __construct(Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $isSanboxMode = $this->helper->isSanboxMode();
        $sdkUrl = $isSanboxMode ? self::JS_SANBOX_SDK : self::JS_PRODUCTTION_SDK;
        $config = [
            'payment' => [
                \Fgc\NabEvm3D\Model\NabEvm3D::CODE => [
                    'isSanboxMode' => $isSanboxMode,
                    'sdkUrl' => $sdkUrl,
                ]
            ]
        ];
        return $config;
    }
}
