define([
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';

        rendererList.push(
            {
                type: 'fgc_nabevm3d',
                component: 'Fgc_NabEvm3D/js/view/payment/method-renderer/nabevm3d'
            }
        );

        /** Add view logic here if needed */
        return Component.extend({});
    });
