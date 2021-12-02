/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery'
], function ($) {
    'use strict';

    var dfd = $.Deferred();

    /**
     * Loads the threeds SDK object
     * @param {String} sdkUrl - the url of the threeds SDK
     */
    return function loadSdkScript(sdkUrl) {
        //configuration for loaded threeds script
        require.config({
            paths: {
                threedsjsSdk: sdkUrl
            },
            shim: {
                threedsjsSdk: {
                    exports: 'SecurePayThreedsUI'
                }
            }
        });

        if (dfd.state() !== 'resolved') {
            require(['threedsjsSdk'], function (sdkObject) {
                dfd.resolve(sdkObject);
            });
        }

        return dfd.promise();
    };
});
