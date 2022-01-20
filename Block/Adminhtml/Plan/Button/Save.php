<?php

namespace Razorpay\Subscription\Block\Adminhtml\Plan\Button;

use Magento\Catalog\Block\Adminhtml\Product\Edit\Button\Generic;
use Magento\Ui\Component\Control\Container;

/**
 * Class Save
 */
class Save extends Generic
{
    /**
     * {@inheritdoc}
     */
    public function getButtonData()
    {

        return [
            'label' => __('Save'),
            'class' => 'save primary',
            'data_attribute' => [
                'mage-init' => [
                    'buttonAdapter' => [
                        'actions' => [
                            [
                                'targetName' => 'product_form.product_form',
                                'actionName' => 'save',
                                'params' => [
                                    false
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'class_name' => Container::DEFAULT_CONTROL,

        ];

    }

    /**
     * Get URL for back
     *
     * @return string
     */
    private function getBackUrl()
    {

        return $this->getUrl('subscribed/*/save');
    }

}

