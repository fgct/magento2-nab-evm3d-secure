define([
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Checkout/js/model/quote',
        'Magento_Payment/js/model/credit-card-validation/credit-card-data',
        'Magento_Ui/js/modal/modal',
        'Magento_Ui/js/modal/alert',
        'Fgc_NabEvm3D/js/threedsjs-sdk',
    ],
    function ($, Component, quote, creditCardData, modal, alert, threedsjsSdk) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Fgc_NabEvm3D/payment/nabevm3d'
            },

            initObservable: function () {
                this.nabConfig = window.checkoutConfig.payment[this.getCode()] || {};
                var sdkUrl = this.nabConfig.sdkUrl;

                this._super();

                this.loadSdkScript(sdkUrl).then(this._setSdkObject.bind(this));
                // window.quote = quote; // Test only
                // window.creditCardData = creditCardData; // Test only
                this.threeDSResultsResponse = {};
                return this;
            },

            context: function() {
                return this;
            },

            getCode: function() {
                return 'fgc_nabevm3d';
            },

            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'cc_cid': this.creditCardVerificationNumber(),
                        'cc_ss_start_month': this.creditCardSsStartMonth(),
                        'cc_ss_start_year': this.creditCardSsStartYear(),
                        'cc_ss_issue': this.creditCardSsIssue(),
                        'cc_type': this.creditCardType(),
                        'cc_exp_year': this.creditCardExpYear(),
                        'cc_exp_month': this.creditCardExpMonth(),
                        'cc_number': this.creditCardNumber(),
                        'liabilityShiftIndicator': this.threeDSResultsResponse.liabilityShiftIndicator,
                        'authenticationValue': this.threeDSResultsResponse.authenticationValue,
                        'eci': this.threeDSResultsResponse.eci,
                        'transStatus': this.threeDSResultsResponse.transStatus,
                        'transStatusReason': this.threeDSResultsResponse.transStatusReason,
                    }
                };
            },

            isActive: function() {
                return true;
            },
            loadSdkScript: function (sdkUrl) {
                return threedsjsSdk(sdkUrl);
            },
            _setSdkObject: function (SecurePayThreedsUI) {
                this.SecurePayThreedsUI = new SecurePayThreedsUI();
            },

            placeOrder: function () {
                this._super();
            },

            validateData: function() {
                var billingAddress = quote.billingAddress();
                var email = window.checkoutConfig.customerData.email || quote.guestEmail;

                var result = true;

                switch (true) {
                    case (!email):
                        alert({
                            content: "Missing email address",
                        });
                        result = false;
                        break;
                    case (!billingAddress || !billingAddress.street || !billingAddress.street[0] || !billingAddress.firstname):
                        alert({
                            content: "Missing billing address",
                        });
                        result = false;
                        break;
                    case (!creditCardData.creditCardNumber || !creditCardData.expirationMonth || !creditCardData.expirationYear):
                        alert({
                            content: "Missing credit card detail",
                        });
                        result = false;
                        break;
                
                    default:
                        break;
                }
                return result;
            },
            getOrderToken: function() {
                if (!this.validateData()) return;

                var self = this;
                $.ajax({
                    showLoader: true,
                    url: '/fgc_nab3d_get_intent/index',
                    data: {
                        // currency: Totals.quote_currency_code,
                        // amount: Totals.grand_total,
                    },
                    type: "POST",
                    dataType: 'json'
                }).done(function (response) {
                    if (!response.success) {
                        alert({
                            content: response.message,
                        });
                        return;
                    }
                    var data = response.data;

                    $('#modal-3ds-v2-challenge-iframe .modal-body-content').html('<iframe id="3ds-v2-challenge-iframe" name="3ds-v2-challenge-iframe" style="width: 100%; height: 500px; border: none;"></iframe>')

                    this.popup = modal({
                        type: 'popup',
                        responsive: true,
                        title: 'EVM 3D Authentication',
                        buttons: []
                    }, $('#modal-3ds-v2-challenge-iframe'));

                    $('#modal-3ds-v2-challenge-iframe').modal('openModal');
                    var iframeElement = document.getElementById("3ds-v2-challenge-iframe");
                    var sp3dsConfig = {
                        clientId: data.clientId,
                        iframe: iframeElement,
                        token: data.orderToken,
                        simpleToken: data.simpleToken,
                        threeDSSessionId: data.sessionId,
                        onRequestInputData: self.onRequestInputDataCallback.bind(self),
                        onThreeDSResultsResponse: self.onThreeDSResultsResponseCallback.bind(self),
                        onThreeDSError: self.onThreeDSErrorCallback.bind(self)
                    };
                    self.SecurePayThreedsUI.initAndStartThreeDS(sp3dsConfig);
                });
            },

            onRequestInputDataCallback: function() {
                var billingAddress = quote.billingAddress();
                var email = window.checkoutConfig.customerData.email || quote.guestEmail;
                var data = {
                    cardholderInfo: {
                        cardholderName: billingAddress.firstname + ' ' + billingAddress.lastname,
                        cardNumber: creditCardData.creditCardNumber,
                        cardExpiryMonth: creditCardData.expirationMonth.length < 2 ? ('0'+ creditCardData.expirationMonth) : creditCardData.expirationMonth,
                        cardExpiryYear: creditCardData.expirationYear.substr(-2),
                    },
                    accountData: {
                        emailAddress: email,
                    },
                    billingAddress: {
                        streetAddress: billingAddress.street[0],
                        city: billingAddress.city,
                        state: billingAddress.regionCode,
                        country: billingAddress.countryId,
                        zipCode: billingAddress.postcode,
                    },
                    threeDSInfo: {
                        threeDSReqAuthMethodInd: '01'
                    }
                }

                return data;
            },
            onThreeDSResultsResponseCallback: function(res) {
                this.threeDSResultsResponse = res;
                $('#modal-3ds-v2-challenge-iframe').modal('closeModal');
                this.placeOrder();
            },
            onThreeDSErrorCallback: function(res) {
                $('#modal-3ds-v2-challenge-iframe').modal('closeModal');
                var errors = res.errors ? res.errors : res;
                if (Array.isArray(errors)) {
                    var message = errors[0].detail;
                    alert({
                        content: message,
                    });
                }
            },
        });
    }
);