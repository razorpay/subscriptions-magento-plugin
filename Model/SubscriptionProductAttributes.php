<?php

namespace Razorpay\Subscription\Model;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class SubscriptionProductAttributes extends AbstractSource
{
    public function getAllOptions()
    {
        if (null === $this->_options) {
            $this->_options=[
                ['label' => __('Subscription Only'), 'value' => "subscriptionOnly"],
                ['label' => __('With Subscription'), 'value' => "withSubscription"],
            ];
        }
        return $this->_options;
    }
}
