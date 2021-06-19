define(
    [
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Customer/js/customer-data',
        'jquery',
        'ko',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/set-payment-information',
        'mage/url',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Ui/js/model/messageList',
        'Magento_Checkout/js/model/shipping-save-processor'
    ],
    function (Component, quote, customerData, $, ko, additionalValidators, setPaymentInformationAction, url, customer, placeOrderAction, fullScreenLoader, messageList, shippingSaveProcessor) {
        'use strict';
        let orderUrl ="";
        let callBackUrl = "";

        return Component.extend({
            defaults: {
                template: 'Razorpay_Subscription/payment/razorpay-subscription-form',
                razorpayDataFrameLoaded: false,
                rzp_response: {}
            },
            getMerchantName: function() {
                return window.checkoutConfig.payment.razorpay.merchant_name;
            },

            getKeyId: function() {
                return window.checkoutConfig.payment.razorpay.key_id;
            },

            context: function() {
                return this;
            },

            isShowLegend: function() {
                return true;
            },

            getCode: function() {
                return 'razorpay_subscription';
            },

            isActive: function() {
                return true;
            },

            isAvailable: function() {
                return this.razorpayDataFrameLoaded;
            },

            handleError: function (error) {
                if (_.isObject(error)) {
                    this.messageContainer.addErrorMessage(error);
                } else {
                    this.messageContainer.addErrorMessage({
                        message: error
                    });
                }
            },

            initObservable: function() {
                var self = this._super();              //Resolves UI Error on Checkout


                if(!self.razorpayDataFrameLoaded) {
                    $.getScript("https://checkout.razorpay.com/v1/checkout.js", function() {
                        self.razorpayDataFrameLoaded = true;
                    });
                }

                return self;
            },

            /**
             * @override
             */
            /** Process Payment */
            preparePayment: function (context, event) {

                if(!additionalValidators.validate()) {   //Resolve checkout aggreement accept error
                    return false;
                }

                var self = this,
                    billing_address,
                    rzp_order_id;

                fullScreenLoader.startLoader();
                this.messageContainer.clear();

                this.amount = quote.totals()['base_grand_total'] * 100;
                billing_address = quote.billingAddress();

                this.user = {
                    name: billing_address.firstname + ' ' + billing_address.lastname,
                    contact: billing_address.telephone,
                };

                if (!customer.isLoggedIn()) {
                    this.user.email = quote.guestEmail;
                }
                else
                {
                    this.user.email = customer.customerData.email;
                }

                this.isPaymentProcessing = $.Deferred();

                $.when(this.isPaymentProcessing).done(
                    function () {
                        self.placeOrder();
                    }
                ).fail(
                    function (result) {
                        self.handleError(result);
                    }
                );

                self.getRzpOrderId();

                return;
            },

            getRzpOrderId: function () {
                var self = this;

                //update shipping and billing before order into quotes
                if(!quote.isVirtual()) {
                    shippingSaveProcessor.saveShippingInformation().success(
                        function (response) {
                            self.createRzpOrder();
                        }
                    ).fail(
                        function (response) {
                            fullScreenLoader.stopLoader();
                            self.isPaymentProcessing.reject(response.message);
                        }
                    );
                } else {
                    self.createRzpOrder();
                }
            },

            isSubscriptionProduct: function(){
                if(customerData.get('cart')().items[0].options.length > 0 && customerData.get('cart')().items[0].options[0].label == "Subscription type"){
                    orderUrl = "razorpay-subscription/subscription/subscriptionorder?";
                    callBackUrl = "razorpay/payment/order";
                    return true;
                } else {
                    orderUrl = "razorpay/payment/order?";
                    callBackUrl = "razorpay/payment/order";
                    return false;
                }
            },

            // Create order
            createRzpOrder: function(){
                var self = this;
                this.isSubscriptionProduct();
                $.ajax({
                    type: 'POST',
                    url: url.build(orderUrl + Math.random().toString(36).substring(10)),
                    data: {
                        email: this.user.email,
                        billing_address: JSON.stringify(quote.billingAddress())
                    },

                    /**
                     * Success callback
                     * @param {Object} response
                     */
                    success: function (response) {
                        fullScreenLoader.stopLoader();
                        if (response.success) {
                            if (response.is_hosted) {
                                self.renderHosted(response);
                            } else {
                                self.renderIframe(response);
                            }
                        } else {
                            self.isPaymentProcessing.reject(response.message);
                        }
                    },


                    /**
                     * Error callback
                     * @param {*} response
                     */
                    error: function (response) {
                        fullScreenLoader.stopLoader();
                        self.isPaymentProcessing.reject(response.message);
                    },

                });
            },
            createInputFieldsFromOptions: function (options, form) {
                var self = this;

                function visitNestedOption(options, parentKey) {
                    for (let curKey in options) {
                        if (options.hasOwnProperty(curKey)) {
                            const value = options[curKey];
                            let prepareKey = parentKey ? `${parentKey}[${curKey}]` : curKey;

                            if (typeof value === 'object') {
                                visitNestedOption(value, prepareKey);
                            } else {
                                // Exception: Rename key -> key_id (merchant key)
                                if (prepareKey === 'key') {
                                    prepareKey = 'key_id';
                                }

                                form.appendChild(self.createHiddenInput(prepareKey, value));
                            }
                        }
                    }
                }
                visitNestedOption(options);
            },

            createHiddenInput: function(key, value) {
                var input = document.createElement('input');

                input.type = 'hidden';
                input.name = key;
                input.value = value;

                return input;
            },

            renderHosted: function(data) {
                var self = this;

                this.merchant_order_id = data.order_id;

                var opts = {
                    key: self.getKeyId(),
                    name: self.getMerchantName(),
                    amount: data.amount,
                    notes: {
                        merchant_order_id: '',
                        merchant_quote_id: data.order_id
                    },
                    prefill: {
                        name: this.user.name,
                        contact: this.user.contact,
                        email: this.user.email
                    },
                    callback_url: url.build(callBackUrl),
                    cancel_url  : url.build('checkout/cart'),
                    _: {
                        integration: 'magento',
                        integration_version: data.module_version,
                        integration_parent_version: data.maze_version,
                    }
                }
                if(this.isSubscriptionProduct()){
                    opts.subscription_id =  data.rzp_order;
                } else {
                    opts.order_id =  data.rzp_order;
                }
                const options = JSON.parse(JSON.stringify(opts));

                var form = document.createElement('form'),
                    method = 'POST',
                    input,
                    key;

                form.method = method;
                form.action = data.embedded_url;

                self.createInputFieldsFromOptions(options, form);

                document.body.appendChild(form);

                form.submit();
            },

            // check for razorpay order
            checkRzpOrder: function (data) {
                var self = this;
                this.isSubscriptionProduct();
                $.ajax({
                    type: 'POST',
                    url: url.build(orderUrl + Math.random().toString(36).substring(10)),
                    data: "order_check=1",

                    /**
                     * Success callback
                     * @param {Object} response
                     */
                    success: function (response) {
                        //fullScreenLoader.stopLoader();
                        if (response.success) {
                            if(response.order_id){
                                $(location).attr('href', 'onepage/success?' + Math.random().toString(36).substring(10));
                            }else{
                                setTimeout(function(){ self.checkRzpOrder(data); }, 1500);
                            }
                        } else {
                            self.placeOrder(data);
                        }
                    },

                    /**
                     * Error callback
                     * @param {*} response
                     */
                    error: function (response) {
                        fullScreenLoader.stopLoader();
                        self.isPaymentProcessing.reject(response.message);
                    }
                });
            },

            renderIframe: function(data) {
                var self = this;

                this.merchant_order_id = data.order_id;

                var options = {
                    key: self.getKeyId(),
                    name: self.getMerchantName(),
                    amount: data.amount,
                    handler: function (data) {
                        self.rzp_response = data;
                        self.checkRzpOrder(data);
                    },
                    modal: {
                        ondismiss: function() {
                            self.isPaymentProcessing.reject("Payment Closed");
                        }
                    },
                    notes: {
                        merchant_order_id: '',
                        merchant_quote_id: data.order_id
                    },
                    prefill: {
                        name: this.user.name,
                        contact: this.user.contact,
                        email: this.user.email
                    },
                    callback_url: url.build(callBackUrl),
                    _: {
                        integration: 'magento',
                        integration_version: data.module_version,
                        integration_parent_version: data.maze_version,
                    }
                };
                if(this.isSubscriptionProduct()){
                    options.subscription_id =  data.rzp_order;
                } else {
                    options.order_id =  data.rzp_order;
                }

                if (data.quote_currency !== 'INR')
                {
                    options.display_currency = data.quote_currency;
                    options.display_amount = data.quote_amount;
                }

                this.rzp = new Razorpay(options);

                this.rzp.open();
            },

            getData: function() {
                return {
                    "method": this.item.method,
                    "po_number": null,
                    "additional_data": {
                        rzp_payment_id: this.rzp_response.razorpay_payment_id,
                        order_id: this.merchant_order_id,
                        rzp_signature: this.rzp_response.razorpay_signature
                    }
                };
            }
        });
    }
);