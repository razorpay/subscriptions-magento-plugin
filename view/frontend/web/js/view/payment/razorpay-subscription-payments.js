define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (Component, rendererList) {
        'use strict';
        rendererList.push(
            {
                type: 'razorpay',
                component: 'Razorpay_Magento/js/view/payment/method-renderer/razorpay-method'
            },
            {
                type: 'razorpay_subscription',
                component: 'Razorpay_Subscription/js/view/payment/method-renderer/razorpay-subscription-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);