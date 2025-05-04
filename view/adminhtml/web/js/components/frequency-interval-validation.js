define([
    'jquery',
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/select'
], function ($, _, uiRegistry, select) {
    'use strict';
    return select.extend({
        /**
         * On value change handler.
         *
         * @param {String} value
         */
        onUpdate: function (planType) {


            let planInterval = uiRegistry.get('index = plan_interval').value()
            if(planType =='daily')
            {
                if((planInterval==='') || (planInterval <'7')){
                    alert('For daily plans, the minimum Billing Frequency is 7.');
                }
            }
        }
    });
});
