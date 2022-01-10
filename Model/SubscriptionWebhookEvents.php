<?php

namespace Razorpay\Subscription\Model;

use Magento\Framework\Data\OptionSourceInterface;
use Razorpay\Magento\Model\WebhookEvents;

class SubscriptionWebhookEvents implements OptionSourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $events = array();
        $webhookEvent = new WebhookEvents();
        foreach ($webhookEvent->toOptionArray() as $value){
            array_push($events, $value);
        }
        $subscriptionEvents = [
            [
                'value' => "subscription.charged",
                'label' => __('subscription.charged')
            ],
            [
                'value' => "subscription.paused",
                'label' => __('subscription.paused')
            ],
            [
                'value' => "subscription.cancelled",
                'label' => __('subscription.cancelled')
            ],
            [
                'value' => "subscription.pending",
                'label' => __('subscription.pending')
            ],
            [
                'value' => "subscription.halted",
                'label' => __('subscription.halted')
            ]
        ];
        foreach ($subscriptionEvents as $value){
            array_push($events, $value);
        }
        return $events;
    }
}
