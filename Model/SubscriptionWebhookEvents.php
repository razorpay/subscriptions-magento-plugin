<?php

namespace Razorpay\Subscription\Model;

use \Magento\Framework\Option\ArrayInterface;
use Razorpay\Magento\Model\WebhookEvents;

class SubscriptionWebhookEvents implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => "order.paid",
                'label' => __('order.paid'),
            ],
            [
                'value' => "subscription.charged",
                'label' => __('subscription.charged'),
            ],
        ];
    }
}
